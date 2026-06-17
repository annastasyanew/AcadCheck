<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rubric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RubricController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['nullable', 'string', 'exists:document_types,name'],
        ]);

        $rubrics = Rubric::query()
            ->with('documentType')
            ->when(
                $validated['document_type'] ?? null,
                fn ($query, string $type) => $query->whereHas('documentType', fn ($query) => $query->where('name', $type)),
            )
            ->orderBy('document_type_id')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $rubrics,
        ]);
    }

    public function update(Request $request, Rubric $rubric): JsonResponse
    {
        $validated = $request->validate([
            'aspect_name' => ['sometimes', 'string', 'max:255'],
            'weight' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rubric->update($validated);

        return response()->json([
            'message' => 'Rubrik berhasil diperbarui.',
            'data' => $rubric->load('documentType'),
        ]);
    }
}
