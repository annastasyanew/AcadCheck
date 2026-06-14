<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TextExtractionException;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\TextExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->with(['documentType', 'latestVersion'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $documents,
        ]);
    }

    public function store(Request $request, TextExtractionService $textExtractionService): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => [
                'required',
                Rule::exists(DocumentType::class, 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ]);

        $storedPath = null;

        try {
            [$document, $version] = DB::transaction(function () use ($request, $validated, $textExtractionService, &$storedPath): array {
                $document = Document::create([
                    'user_id' => $request->user()->id,
                    'document_type_id' => $validated['document_type_id'],
                    'title' => $validated['title'],
                    'topic' => $validated['topic'] ?? null,
                    'keywords' => $validated['keywords'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'status' => Document::STATUS_UPLOADED,
                ]);

                $file = $request->file('file');
                $storedPath = $file->store(
                    "documents/user_{$request->user()->id}/document_{$document->id}",
                    'local',
                );

                if ($storedPath === false) {
                    throw new \RuntimeException('Dokumen gagal disimpan.');
                }

                $extractedText = $textExtractionService->extract(
                    Storage::disk('local')->path($storedPath),
                    $file->getClientOriginalExtension(),
                );

                $version = $document->versions()->create([
                    'version_number' => 1,
                    'file_path' => $storedPath,
                    'file_original_name' => $file->getClientOriginalName(),
                    'file_type' => strtolower($file->getClientOriginalExtension()),
                    'file_size' => $file->getSize(),
                    'extracted_text' => $extractedText,
                    'uploaded_at' => now(),
                ]);

                $document->update([
                    'latest_version_id' => $version->id,
                ]);

                return [$document, $version];
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
            'message' => 'Dokumen berhasil diunggah dan teks berhasil diekstrak.',
            'data' => [
                'document' => $document->load('documentType'),
                'version' => $version,
                'extracted_text_preview' => mb_substr($version->extracted_text, 0, 500),
            ],
        ], 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        if ($document->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke dokumen ini.',
            ], 403);
        }

        return response()->json([
            'data' => $document->load([
                'documentType',
                'versions',
                'latestVersion',
                'analysisResults.aspectScores',
            ]),
        ]);
    }
}
