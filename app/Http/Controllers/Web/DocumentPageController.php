<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DocumentPageController extends Controller
{
    public function index(): View
    {
        return view('user.documents.index');
    }

    public function upload(): View
    {
        return view('user.documents.upload');
    }

    public function show(int $document): View
    {
        return view('user.documents.show', ['documentId' => $document]);
    }

    public function revisionUpload(int $document): View
    {
        return view('user.documents.upload-revision', ['documentId' => $document]);
    }

    public function comparison(int $document): View
    {
        return view('user.documents.comparison', ['documentId' => $document]);
    }

    public function reviewerMapping(int $document): View
    {
        return view('user.articles.reviewer-mapping', ['documentId' => $document]);
    }
}
