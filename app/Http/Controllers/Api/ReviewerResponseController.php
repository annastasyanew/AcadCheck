<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Exceptions\AuthorResponseGeneratorException;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ReviewerComment;
use App\Services\AuthorResponseGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ReviewerResponseController extends Controller
{
    public function storeOrUpdate(Request $request, ReviewerComment $reviewerComment): JsonResponse
    {
        $document = $reviewerComment->document;

        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly('Respons reviewer');
        }

        $validated = $request->validate([
            'author_response' => ['required', 'string'],
            'revision_made' => ['nullable', 'string'],
            'revision_location' => ['nullable', 'string', 'max:255'],
            'revised_version_id' => [
                'nullable',
                'integer',
                Rule::exists('document_versions', 'id')->where('document_id', $document->id),
            ],
        ]);

        $response = $reviewerComment->response()->updateOrCreate([], $validated);

        $reviewerComment->update([
            'status' => ReviewerComment::STATUS_DONE,
        ]);

        return response()->json([
            'message' => 'Respons penulis berhasil disimpan.',
            'data' => $response->load('revisedVersion'),
        ]);
    }

    public function generateResponse(
        Request $request,
        ReviewerComment $reviewerComment,
        AuthorResponseGeneratorService $generatorService,
    ): JsonResponse {
        $document = $reviewerComment->document;

        if (! $this->canAccess($request, $document)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke komentar ini.',
            ], 403);
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly('Generate respons reviewer');
        }

        $validated = $request->validate([
            'revision_made' => ['required', 'string', 'max:10000'],
            'revision_location' => ['nullable', 'string', 'max:255'],
            'save_to_database' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $generatorService->generate(
                $reviewerComment,
                $validated['revision_made'],
                $validated['revision_location'] ?? null,
            );
            $savedResponse = null;

            if ($validated['save_to_database'] ?? false) {
                $savedResponse = DB::transaction(function () use ($reviewerComment, $validated, $result) {
                    $response = $reviewerComment->response()->updateOrCreate([], [
                        'author_response' => $result['author_response'],
                        'revision_made' => $validated['revision_made'],
                        'revision_location' => $validated['revision_location'] ?? null,
                    ]);

                    $reviewerComment->update([
                        'status' => ReviewerComment::STATUS_DONE,
                    ]);

                    return $response;
                });
            }
        } catch (AuthorResponseGeneratorException|AiProviderException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Gagal membuat draft respons penulis dengan AI. Silakan coba kembali.',
            ], 502);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Gagal membuat draft respons penulis karena kesalahan internal.',
            ], 500);
        }

        return response()->json([
            'message' => 'Draft respons penulis berhasil dibuat.',
            'data' => [
                'generated_response' => $result,
                'saved_response' => $savedResponse,
            ],
        ]);
    }

    public function matrix(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly('Response matrix');
        }

        $comments = $document->reviewerComments()
            ->with(['response.revisedVersion'])
            ->orderBy('reviewer_label')
            ->orderBy('comment_number')
            ->orderBy('id')
            ->get()
            ->map(fn (ReviewerComment $comment): array => [
                'reviewer_comment_id' => $comment->id,
                'reviewer' => $comment->reviewer_label,
                'comment_number' => $comment->comment_number,
                'original_comment' => $comment->original_comment,
                'related_section' => $comment->related_section,
                'priority' => $comment->priority,
                'status' => $comment->status,
                'author_response' => $comment->response?->author_response,
                'revision_made' => $comment->response?->revision_made,
                'revision_location' => $comment->response?->revision_location,
                'revised_version_id' => $comment->response?->revised_version_id,
                'revised_version_number' => $comment->response?->revisedVersion?->version_number,
            ]);

        return response()->json([
            'data' => [
                'document_id' => $document->id,
                'title' => $document->title,
                'response_matrix' => $comments,
            ],
        ]);
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }

    private function isArticle(Document $document): bool
    {
        return $document->documentType()->where('name', 'article')->exists();
    }

    private function accessDenied(): JsonResponse
    {
        return response()->json([
            'message' => 'Anda tidak memiliki akses ke dokumen ini.',
        ], 403);
    }

    private function articleOnly(string $feature): JsonResponse
    {
        return response()->json([
            'message' => "{$feature} hanya tersedia untuk artikel ilmiah.",
        ], 422);
    }
}
