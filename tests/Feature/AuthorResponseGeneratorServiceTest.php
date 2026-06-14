<?php

namespace Tests\Feature;

use App\Exceptions\AuthorResponseGeneratorException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\ReviewerComment;
use App\Models\User;
use App\Services\AuthorResponseGeneratorService;
use App\Services\GroqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthorResponseGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_validates_bilingual_author_response(): void
    {
        $comment = $this->createComment();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(fn (array $messages): bool => str_contains(
                    $messages[1]['content'],
                    '<revision-made>',
                ))
                ->andReturn(<<<'JSON'
```json
{
  "author_response": " Thank you for the valuable comment. ",
  "author_response_id": " Terima kasih atas komentar yang diberikan. ",
  "revision_summary": " Menambahkan detail metode. ",
  "tone": "polite"
}
```
JSON);
        });

        $result = app(AuthorResponseGeneratorService::class)->generate(
            $comment,
            'Menambahkan detail metode.',
            'Section 3.2',
        );

        $this->assertSame('Thank you for the valuable comment.', $result['author_response']);
        $this->assertSame('Terima kasih atas komentar yang diberikan.', $result['author_response_id']);
        $this->assertSame('Menambahkan detail metode.', $result['revision_summary']);
        $this->assertSame('polite', $result['tone']);
    }

    public function test_it_rejects_invalid_generated_response_structure(): void
    {
        $comment = $this->createComment();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')->once()->andReturn(<<<'JSON'
{"author_response":"Response.","tone":"aggressive"}
JSON);
        });

        $this->expectException(AuthorResponseGeneratorException::class);
        $this->expectExceptionMessage('Struktur draft respons penulis tidak valid.');

        app(AuthorResponseGeneratorService::class)->generate($comment, 'Revision.');
    }

    private function createComment(): ReviewerComment
    {
        $user = User::factory()->create();
        $type = DocumentType::create([
            'name' => 'article',
            'label' => 'Artikel Ilmiah',
            'is_active' => true,
        ]);
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Artikel Generator',
            'status' => Document::STATUS_REVISED,
        ]);

        return $document->reviewerComments()->create([
            'reviewer_label' => 'Reviewer 1',
            'comment_number' => 1,
            'original_comment' => 'Please improve the methodology.',
            'related_section' => 'Metode',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
            'status' => ReviewerComment::STATUS_PENDING,
        ]);
    }
}
