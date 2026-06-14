<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'document_type' => ['nullable', 'string', 'exists:document_types,name'],
            'status' => [
                'nullable',
                Rule::in([
                    Document::STATUS_UPLOADED,
                    Document::STATUS_ANALYZED,
                    Document::STATUS_NEED_REVISION,
                    Document::STATUS_REVISED,
                    Document::STATUS_READY,
                    'archived',
                ]),
            ],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $documents = Document::query()
            ->with(['user:id,name,email,is_active', 'documentType', 'latestVersion'])
            ->withCount(['versions', 'analysisResults', 'reviewerComments'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['user_id'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when(
                $validated['document_type'] ?? null,
                fn ($query, string $type) => $query->whereHas('documentType', fn ($query) => $query->where('name', $type)),
            )
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json([
            'data' => $documents,
        ]);
    }
}
