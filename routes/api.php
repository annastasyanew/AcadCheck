<?php

use App\Http\Controllers\Api\AdminDocumentController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComparisonController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\ResponseLetterController;
use App\Http\Controllers\Api\ReviewerCommentController;
use App\Http\Controllers\Api\ReviewerResponseController;
use App\Http\Controllers\Api\RubricController;
use App\Services\AiProviderService;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user/dashboard', [DashboardController::class, 'userDashboard']);
    Route::get('/test-ai', function (AiProviderService $aiProviderService) {
        abort_unless(app()->isLocal(), 404);

        return response()->json([
            'message' => $aiProviderService->getContent([
                [
                    'role' => 'user',
                    'content' => 'Jawab singkat: koneksi AI aktif?',
                ],
            ]),
            'provider' => config('services.ai.provider'),
            'model' => config('services.ai.model'),
        ]);
    });

    Route::get('/document-types', [DocumentTypeController::class, 'index']);
    Route::get('/rubrics', [RubricController::class, 'index']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::post('/documents/{document}/analyze', [AnalysisController::class, 'analyze']);
    Route::get('/documents/{document}/analysis', [AnalysisController::class, 'latest']);
    Route::get('/documents/{document}/versions', [DocumentVersionController::class, 'index']);
    Route::post('/documents/{document}/versions', [DocumentVersionController::class, 'store']);
    Route::get('/documents/{document}/comparison', [ComparisonController::class, 'compare']);

    Route::get('/articles/{document}/reviewer-comments', [ReviewerCommentController::class, 'index']);
    Route::post('/articles/{document}/reviewer-comments', [ReviewerCommentController::class, 'store']);
    Route::post('/articles/{document}/reviewer-comments/parse', [ReviewerCommentController::class, 'parseWithAi']);
    Route::put('/reviewer-comments/{reviewerComment}', [ReviewerCommentController::class, 'update']);
    Route::put('/reviewer-comments/{reviewerComment}/status', [ReviewerCommentController::class, 'updateStatus']);
    Route::delete('/reviewer-comments/{reviewerComment}', [ReviewerCommentController::class, 'destroy']);
    Route::post('/reviewer-comments/{reviewerComment}/responses', [ReviewerResponseController::class, 'storeOrUpdate']);
    Route::post('/reviewer-comments/{reviewerComment}/generate-response', [ReviewerResponseController::class, 'generateResponse']);
    Route::get('/articles/{document}/response-matrix', [ReviewerResponseController::class, 'matrix']);
    Route::get('/articles/{document}/response-letter', [ResponseLetterController::class, 'download']);

    Route::middleware('admin')->prefix('admin')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::get('/documents', [AdminDocumentController::class, 'index']);
        Route::put('/rubrics/{rubric}', [RubricController::class, 'update']);
    });
});
