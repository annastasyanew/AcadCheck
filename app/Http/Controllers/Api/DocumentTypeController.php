<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;

class DocumentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => DocumentType::query()
                ->where('is_active', true)
                ->orderBy('label')
                ->get(['id', 'name', 'label', 'description']),
        ]);
    }
}
