<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\Document;
use App\Models\ReviewerComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userDashboard(Request $request): JsonResponse
    {
        $documents = Document::query()->where('user_id', $request->user()->id);

        $latestDocuments = (clone $documents)
            ->with(['documentType', 'latestVersion'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $revisionPriorities = (clone $documents)
            ->with(['documentType', 'latestVersion'])
            ->where('status', Document::STATUS_NEED_REVISION)
            ->orderByRaw('latest_score IS NULL, latest_score ASC')
            ->latest('updated_at')
            ->limit(3)
            ->get();

        return response()->json([
            'data' => [
                'summary' => $this->documentSummary($documents),
                'reviewer_comments' => [
                    'total' => ReviewerComment::whereHas(
                        'document',
                        fn (Builder $query) => $query->where('user_id', $request->user()->id),
                    )->count(),
                    'pending' => ReviewerComment::whereHas(
                        'document',
                        fn (Builder $query) => $query->where('user_id', $request->user()->id),
                    )->where('status', ReviewerComment::STATUS_PENDING)->count(),
                ],
                'latest_documents' => $latestDocuments,
                'latest_activities' => $latestDocuments,
                'revision_priorities' => $revisionPriorities,
                'latest_analyses' => AnalysisResult::query()
                    ->whereHas(
                        'document',
                        fn (Builder $query) => $query->where('user_id', $request->user()->id),
                    )
                    ->with(['document:id,title', 'documentVersion:id,document_id,version_number'])
                    ->latest()
                    ->limit(5)
                    ->get(),
            ],
        ]);
    }

    public function adminDashboard(): JsonResponse
    {
        $documents = Document::query();

        return response()->json([
            'data' => [
                'summary' => [
                    'total_users' => User::count(),
                    'total_regular_users' => User::where('role', User::ROLE_USER)->count(),
                    'total_admins' => User::where('role', User::ROLE_ADMIN)->count(),
                    'active_users' => User::where('is_active', true)->count(),
                    'inactive_users' => User::where('is_active', false)->count(),
                    'total_analysis' => AnalysisResult::count(),
                    ...$this->documentSummary($documents),
                ],
                'latest_documents' => Document::query()
                    ->with(['user:id,name,email', 'documentType', 'latestVersion'])
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(),
                'latest_analyses' => AnalysisResult::query()
                    ->with([
                        'document:id,user_id,title',
                        'document.user:id,name,email',
                        'documentVersion:id,document_id,version_number',
                    ])
                    ->latest()
                    ->limit(5)
                    ->get(),
            ],
        ]);
    }

    private function documentSummary(Builder $documents): array
    {
        return [
            'total_documents' => (clone $documents)->count(),
            'average_score' => round((float) ((clone $documents)->avg('latest_score') ?? 0), 2),
            'by_type' => [
                'article' => $this->countByType($documents, 'article'),
                'proposal' => $this->countByType($documents, 'proposal'),
                'report' => $this->countByType($documents, 'report'),
            ],
            'by_status' => [
                Document::STATUS_UPLOADED => $this->countByStatus($documents, Document::STATUS_UPLOADED),
                Document::STATUS_ANALYZED => $this->countByStatus($documents, Document::STATUS_ANALYZED),
                Document::STATUS_NEED_REVISION => $this->countByStatus($documents, Document::STATUS_NEED_REVISION),
                Document::STATUS_REVISED => $this->countByStatus($documents, Document::STATUS_REVISED),
                Document::STATUS_READY => $this->countByStatus($documents, Document::STATUS_READY),
            ],
        ];
    }

    private function countByType(Builder $documents, string $type): int
    {
        return (clone $documents)
            ->whereHas('documentType', fn (Builder $query) => $query->where('name', $type))
            ->count();
    }

    private function countByStatus(Builder $documents, string $status): int
    {
        return (clone $documents)->where('status', $status)->count();
    }
}
