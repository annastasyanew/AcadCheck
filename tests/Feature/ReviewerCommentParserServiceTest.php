<?php

namespace Tests\Feature;

use App\Exceptions\ReviewerCommentParserException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\ReviewerComment;
use App\Models\User;
use App\Services\GroqService;
use App\Services\ReviewerCommentParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ReviewerCommentParserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_normalizes_and_numbers_reviewer_comments(): void
    {
        $document = $this->createArticle();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(fn (array $messages): bool => str_contains(
                    $messages[1]['content'],
                    '<reviewer-notes>',
                ))
                ->andReturn(<<<'JSON'
```json
{
  "comments": [
    {
      "reviewer_label": "Reviewer 1",
      "original_comment": "The methodology needs more detail.",
      "related_section": "Metode",
      "priority": "critical"
    },
    {
      "reviewer_label": "Reviewer 1",
      "original_comment": "Fix several typos.",
      "related_section": "Bahasa",
      "priority": "minor"
    },
    {
      "reviewer_label": "",
      "comment_number": 4,
      "original_comment": "Clarify the conclusion.",
      "related_section": null,
      "priority": "major"
    }
  ]
}
```
JSON);
        });

        $result = app(ReviewerCommentParserService::class)->parse($document, 'Reviewer notes.');

        $this->assertCount(3, $result['comments']);
        $this->assertSame(1, $result['comments'][0]['comment_number']);
        $this->assertSame(2, $result['comments'][1]['comment_number']);
        $this->assertSame('Reviewer 1', $result['comments'][2]['reviewer_label']);
        $this->assertSame('Lainnya', $result['comments'][2]['related_section']);
        $this->assertSame(ReviewerComment::STATUS_PENDING, $result['comments'][0]['status']);
    }

    public function test_it_rejects_invalid_parser_output(): void
    {
        $document = $this->createArticle();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')->once()->andReturn(<<<'JSON'
{"comments":[{"original_comment":"Comment.","priority":"unknown"}]}
JSON);
        });

        $this->expectException(ReviewerCommentParserException::class);
        $this->expectExceptionMessage('Struktur hasil parser komentar reviewer tidak valid.');

        app(ReviewerCommentParserService::class)->parse($document, 'Reviewer notes.');
    }

    private function createArticle(): Document
    {
        $user = User::factory()->create();
        $type = DocumentType::create([
            'name' => 'article',
            'label' => 'Artikel Ilmiah',
            'is_active' => true,
        ]);

        return Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Artikel Parser',
            'status' => Document::STATUS_REVISED,
        ]);
    }
}
