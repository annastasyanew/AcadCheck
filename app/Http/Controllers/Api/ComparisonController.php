<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComparisonController extends Controller
{
    public function compare(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke dokumen ini.',
            ], 403);
        }

        $validated = $request->validate([
            'from_version_id' => [
                'required',
                'integer',
                Rule::exists('document_versions', 'id')->where('document_id', $document->id),
            ],
            'to_version_id' => [
                'required',
                'integer',
                'different:from_version_id',
                Rule::exists('document_versions', 'id')->where('document_id', $document->id),
            ],
        ]);

        $fromAnalysis = $this->latestAnalysis($document, $validated['from_version_id']);
        $toAnalysis = $this->latestAnalysis($document, $validated['to_version_id']);

        if (! $fromAnalysis || ! $toAnalysis) {
            return response()->json([
                'message' => 'Kedua versi harus sudah dianalisis terlebih dahulu.',
            ], 422);
        }

        $fromAspects = $fromAnalysis->aspectScores->keyBy('aspect_name');
        $toAspects = $toAnalysis->aspectScores->keyBy('aspect_name');
        $aspectNames = $fromAspects->keys()->merge($toAspects->keys())->unique()->sort()->values();

        $comparison = $aspectNames->map(function (string $aspectName) use ($fromAspects, $toAspects): array {
            $fromScore = (int) ($fromAspects->get($aspectName)?->score ?? 0);
            $toScore = (int) ($toAspects->get($aspectName)?->score ?? 0);
            $difference = $toScore - $fromScore;

            return [
                'aspect_name' => $aspectName,
                'from_score' => $fromScore,
                'to_score' => $toScore,
                'difference' => $difference,
                'status' => $this->changeStatus($difference),
            ];
        });

        $totalDifference = $toAnalysis->total_score - $fromAnalysis->total_score;

        return response()->json([
            'data' => [
                'document_id' => $document->id,
                'from_version_id' => (int) $validated['from_version_id'],
                'to_version_id' => (int) $validated['to_version_id'],
                'from_analysis_id' => $fromAnalysis->id,
                'to_analysis_id' => $toAnalysis->id,
                'from_total_score' => $fromAnalysis->total_score,
                'to_total_score' => $toAnalysis->total_score,
                'total_difference' => $totalDifference,
                'total_status' => $this->changeStatus($totalDifference),
                'aspect_comparison' => $comparison,
            ],
        ]);
    }

    private function latestAnalysis(Document $document, int $versionId): ?AnalysisResult
    {
        return $document->analysisResults()
            ->with('aspectScores')
            ->where('document_version_id', $versionId)
            ->latest()
            ->first();
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }

    private function changeStatus(int $difference): string
    {
        return match (true) {
            $difference > 0 => 'improved',
            $difference < 0 => 'declined',
            default => 'unchanged',
        };
    }
}
