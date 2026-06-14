<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_only_active_document_types(): void
    {
        $activeType = DocumentType::create([
            'name' => 'article',
            'label' => 'Artikel Ilmiah',
            'is_active' => true,
        ]);

        DocumentType::create([
            'name' => 'archived',
            'label' => 'Tipe Nonaktif',
            'is_active' => false,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/document-types')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeType->id)
            ->assertJsonMissing(['label' => 'Tipe Nonaktif']);
    }

    public function test_document_types_require_authentication(): void
    {
        $this->getJson('/api/document-types')
            ->assertUnauthorized();
    }
}
