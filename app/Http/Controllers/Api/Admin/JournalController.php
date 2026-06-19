<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\JournalEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $query = Journal::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('publisher', 'like', '%' . $request->search . '%')
                    ->orWhere('subject_area', 'like', '%' . $request->search . '%')
                    ->orWhere('keywords', 'like', '%' . $request->search . '%')
                    ->orWhere('focus_scope', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('sinta_level')) {
            $query->where('sinta_level', $request->sinta_level);
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $journals = $query
            ->orderByRaw("FIELD(sinta_level, 'S1','S2','S3','S4','S5','S6')")
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => $journals,
        ]);
    }

    public function stats(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $bySinta = Journal::query()
            ->selectRaw('COALESCE(sinta_level, ?) as sinta_level, COUNT(*) as total', ['Belum diisi'])
            ->groupBy('sinta_level')
            ->orderByRaw("CASE sinta_level WHEN 'S1' THEN 1 WHEN 'S2' THEN 2 WHEN 'S3' THEN 3 WHEN 'S4' THEN 4 WHEN 'S5' THEN 5 WHEN 'S6' THEN 6 ELSE 7 END")
            ->get()
            ->map(fn ($item) => [
                'sinta_level' => $item->sinta_level,
                'total' => (int) $item->total,
            ]);

        return response()->json([
            'data' => [
                'total' => Journal::count(),
                'active' => Journal::where('is_active', true)->count(),
                'pending_review' => Journal::where('verification_status', 'pending_review')->count(),
                'verified' => Journal::where('verification_status', 'verified')->count(),
                'ai_ready' => Journal::query()
                    ->where('is_active', true)
                    ->where('verification_status', 'verified')
                    ->where('eligibility_score', '>=', JournalEligibilityService::MINIMUM_AI_SCORE)
                    ->count(),
                'minimum_ai_score' => JournalEligibilityService::MINIMUM_AI_SCORE,
                'by_sinta' => $bySinta,
            ],
        ]);
    }

    public function import(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = fopen($request->file('file')->getRealPath(), 'r');

        $header = fgetcsv($file);

        if (! $header) {
            fclose($file);

            return response()->json([
                'message' => 'CSV kosong atau format tidak valid.',
            ], 422);
        }

        $header = array_map(function ($item) {
            $item = preg_replace('/^\xEF\xBB\xBF/', '', (string) $item);

            return strtolower(trim($item));
        }, $header);

        if (! in_array('name', $header)) {
            fclose($file);

            return response()->json([
                'message' => 'Kolom wajib name tidak ditemukan.',
            ], 422);
        }

        $imported = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) {
                $failed++;
                continue;
            }

            $data = array_combine($header, $row);

            if (! $data || empty(trim($data['name'] ?? ''))) {
                $failed++;
                continue;
            }

            $validator = Validator::make($data, [
                'name' => ['required', 'string'],
                'sinta_level' => ['nullable', 'string'],
                'subject_area' => ['nullable', 'string'],
                'website_url' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $failed++;

                $errors[] = [
                    'name' => $data['name'] ?? '-',
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            $journalData = [
                'name' => $data['name'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'sinta_level' => $data['sinta_level'] ?? null,
                'subject_area' => $data['subject_area'] ?? null,
                'focus_scope' => $data['focus_scope'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'p_issn' => $this->normalizeIssn($data['p_issn'] ?? null),
                'e_issn' => $this->normalizeIssn($data['e_issn'] ?? null),
                'website_url' => $data['website_url'] ?? null,
                'editor_url' => $data['editor_url'] ?? null,
                'template_url' => $data['template_url'] ?? null,
                'author_guideline_url' => $data['author_guideline_url'] ?? null,
                'indexing' => $data['indexing'] ?? null,
                'impact' => $data['impact'] ?? null,
                'h5_index' => $data['h5_index'] ?? null,
                'citations_5yr' => $data['citations_5yr'] ?? null,
                'citations_total' => $data['citations_total'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'raw_text' => $data['raw_text'] ?? null,
                'last_verified_at' => ! empty($data['last_verified_at']) ? $data['last_verified_at'] : null,

                // Data hasil CSV tidak langsung dipakai untuk rekomendasi.
                'is_active' => false,
                'verification_status' => 'pending_review',
            ];

            if (empty($journalData['publisher']) && ! empty($journalData['raw_text'])) {
                $journalData['publisher'] = $this->extractPublisherFromRawText(
                    $journalData['name'],
                    $journalData['raw_text']
                );
            }

            $existing = $this->findExistingJournal($journalData);

            if ($existing) {
                $existing->update($journalData);
                $updated++;
            } else {
                Journal::create($journalData);
                $imported++;
            }
        }

        fclose($file);

        return response()->json([
            'message' => 'Import CSV jurnal selesai.',
            'summary' => [
                'imported' => $imported,
                'updated' => $updated,
                'failed' => $failed,
            ],
            'errors' => $errors,
        ]);
    }

    public function update(Request $request, Journal $journal)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string'],
            'publisher' => ['nullable', 'string'],
            'sinta_level' => ['nullable', 'string'],
            'subject_area' => ['nullable', 'string'],
            'focus_scope' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'website_url' => ['nullable', 'string'],
            'editor_url' => ['nullable', 'string'],
            'template_url' => ['nullable', 'string'],
            'author_guideline_url' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'verification_status' => ['nullable', 'string'],
        ]);

        $journal->update($validated);

        return response()->json([
            'message' => 'Data jurnal berhasil diperbarui.',
            'data' => $journal,
        ]);
    }

    public function destroy(Request $request, Journal $journal)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $journal->delete();

        return response()->json([
            'message' => 'Data jurnal berhasil dihapus.',
        ]);
    }

    private function normalizeIssn(?string $issn): ?string
    {
        $issn = trim((string) $issn);

        if ($issn === '' || $issn === '0') {
            return null;
        }

        return $issn;
    }

    private function findExistingJournal(array $journalData): ?Journal
    {
        if (! empty($journalData['e_issn'])) {
            $journal = Journal::where('e_issn', $journalData['e_issn'])->first();

            if ($journal) {
                return $journal;
            }
        }

        if (! empty($journalData['p_issn'])) {
            $journal = Journal::where('p_issn', $journalData['p_issn'])->first();

            if ($journal) {
                return $journal;
            }
        }

        return Journal::where('name', $journalData['name'])->first();
    }

    private function extractPublisherFromRawText(string $name, string $rawText): ?string
    {
        $text = trim($rawText);

        $text = str_replace($name, '', $text);
        $text = str_replace('Google Scholar Website Editor URL', '', $text);
        $text = str_replace('Website Editor URL', '', $text);
        $text = str_replace('Google Scholar', '', $text);

        $parts = preg_split('/P-ISSN\s*:/i', $text);

        if (! $parts || empty($parts[0])) {
            return null;
        }

        $publisher = trim($parts[0]);

        return $publisher !== '' ? substr($publisher, 0, 255) : null;
    }
}
