@extends('layouts.app', ['title' => $heading . ' | AcadCheck AI'])

@section('page', 'document-action-placeholder')

@section('content')
<main class="destination-shell" data-document-id="{{ $documentId }}">
    <div class="destination-card">
        <span class="brand-icon">A</span>
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $description }}</p>
        <div class="destination-actions">
            <a href="/documents/{{ $documentId }}" class="primary-button button-link">Kembali ke detail</a>
            <a href="/documents" class="secondary-button button-link">Document Library</a>
            <button type="button" class="secondary-button" data-logout>Keluar</button>
        </div>
    </div>
</main>
@endsection
