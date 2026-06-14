<?php

namespace Tests\Unit;

use App\Exceptions\GroqException;
use App\Services\GroqService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqServiceTest extends TestCase
{
    public function test_it_sends_chat_completion_request_and_returns_content(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.base_url' => 'https://api.groq.test/openai/v1',
            'services.groq.model' => 'test-model',
        ]);
        Http::fake([
            'api.groq.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"summary":"ok"}']],
                ],
            ]),
        ]);

        $content = app(GroqService::class)->getContent([
            ['role' => 'user', 'content' => 'Analyze'],
        ]);

        $this->assertSame('{"summary":"ok"}', $content);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.groq.test/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'test-model';
        });
    }

    public function test_it_returns_safe_exception_for_failed_authentication(): void
    {
        config([
            'services.groq.api_key' => 'invalid-key',
            'services.groq.base_url' => 'https://api.groq.test/openai/v1',
        ]);
        Http::fake([
            'api.groq.test/*' => Http::response(['error' => 'Sensitive upstream body'], 401),
        ]);

        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Konfigurasi autentikasi layanan AI tidak valid.');

        app(GroqService::class)->getContent([
            ['role' => 'user', 'content' => 'Analyze'],
        ]);
    }
}
