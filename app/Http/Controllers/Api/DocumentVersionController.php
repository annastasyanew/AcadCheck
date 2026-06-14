<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TextExtractionException;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\TextExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentVersionController extends Controller
{
    public function index(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke dokumen ini.',
            ], 403);
        }

        return response()->json([
            'data' => $document->versions()
                ->withCount('analysisResults')
                ->orderBy('version_number')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        Document $document,
        TextExtractionService $textExtractionService,
    ): JsonResponse {
        if ($document->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengunggah revisi dokumen ini.',
            ], 403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'revision_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $storedPath = null;

        try {
            $version = DB::transaction(function () use (
                $request,
                $document,
                $validated,
                $textExtractionService,
                &$storedPath,
            ) {
                $lockedDocument = Document::query()
                    ->whereKey($document->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nextVersionNumber = ((int) $lockedDocument->versions()->max('version_number')) + 1;
                $file = $request->file('file');
                $storedPath = $file->store(
                    "document-revisions/user_{$request->user()->id}/document_{$lockedDocument->id}",
                    'local',
                );

                if ($storedPath === false) {
                    throw new \RuntimeException('Dokumen revisi gagal disimpan.');
                }

                $extractedText = $textExtractionService->extract(
                    Storage::disk('local')->path($storedPath),
                    $file->getClientOriginalExtension(),
                );

                $version = $lockedDocument->versions()->create([
                    'version_number' => $nextVersionNumber,
                    'file_path' => $storedPath,
                    'file_original_name' => $file->getClientOriginalName(),
                    'file_type' => strtolower($file->getClientOriginalExtension()),
                    'file_size' => $file->getSize(),
                    'extracted_text' => $extractedText,
                    'revision_note' => $validated['revision_note'] ?? null,
                    'uploaded_at' => now(),
                ]);

                $lockedDocument->update([
                    'latest_version_id' => $version->id,
                    'latest_score' => null,
                    'status' => Document::STATUS_REVISED,
                ]);

                return $version;
            });
        } catch (TextExtractionException $exception) {
            report($exception);

            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'Versi revisi berhasil diunggah.',
            'data' => [
                'version' => $version,
                'extracted_text_preview' => mb_substr($version->extracted_text, 0, 500),
            ],
        ], 201);
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }
}
