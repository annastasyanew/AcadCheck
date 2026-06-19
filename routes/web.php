<?php

use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\DashboardPageController;
use App\Http\Controllers\Web\DocumentPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [AuthPageController::class, 'login'])->name('login.page');
Route::get('/register', [AuthPageController::class, 'register'])->name('register.page');
Route::get('/dashboard', [DashboardPageController::class, 'user'])->name('dashboard.page');
Route::get('/documents', [DocumentPageController::class, 'index'])->name('documents.index.page');
Route::get('/documents/upload', [DocumentPageController::class, 'upload'])->name('documents.upload.page');
Route::get('/documents/{document}', [DocumentPageController::class, 'show'])
    ->whereNumber('document')
    ->name('documents.show.page');
Route::get('/documents/{document}/revisions/upload', [DocumentPageController::class, 'revisionUpload'])
    ->whereNumber('document')
    ->name('documents.revisions.upload.page');
Route::get('/documents/{document}/comparison', [DocumentPageController::class, 'comparison'])
    ->whereNumber('document')
    ->name('documents.comparison.page');
Route::get('/articles/{document}/reviewer-mapping', [DocumentPageController::class, 'reviewerMapping'])
    ->whereNumber('document')
    ->name('articles.reviewer-mapping.page');
Route::get('/admin/dashboard', [DashboardPageController::class, 'admin'])->name('admin.dashboard.page');
Route::get('/admin/users', [DashboardPageController::class, 'adminUsers'])->name('admin.users.page');
Route::get('/admin/documents', [DashboardPageController::class, 'adminDocuments'])->name('admin.documents.page');
Route::get('/admin/rubrics', [DashboardPageController::class, 'adminRubrics'])->name('admin.rubrics.page');
Route::get('/admin/journals', [DashboardPageController::class, 'adminJournals'])->name('admin.journals.page');
