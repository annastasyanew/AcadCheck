<?php

namespace Tests\Unit;

use App\Exceptions\AiProviderException;
use App\Services\AiProviderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderServiceTest extends TestCase
{
    public function test_it_sends_chat_completion_request_and_returns_content(): void
    {
        config([
            'services.ai.api_key' => 'test-key',
            'services.ai.base_url' => 'https://api.provider.test/v1',
            'services.ai.model' => 'test-model',
        ]);
        Http::fake([
            'api.provider.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"summary":"ok"}']],
                ],
            ]),
        ]);

        $content = app(AiProviderService::class)->getContent([
            ['role' => 'user', 'content' => 'Analyze'],
        ]);

        $this->assertSame('{"summary":"ok"}', $content);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.provider.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'test-model';
        });
    }

    public function test_it_returns_safe_exception_for_failed_authentication(): void
    {
        config([
            'services.ai.api_key' => 'invalid-key',
            'services.ai.base_url' => 'https://api.provider.test/v1',
            'services.ai.model' => 'test-model',
        ]);
        Http::fake([
            'api.provider.test/*' => Http::response(['error' => 'Sensitive upstream body'], 401),
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('Konfigurasi autentikasi layanan AI tidak valid.');

        app(AiProviderService::class)->getContent([
            ['role' => 'user', 'content' => 'Analyze'],
        ]);
    }

    public function test_it_requires_complete_provider_configuration(): void
    {
        config([
            'services.ai.api_key' => null,
            'services.ai.base_url' => 'https://api.provider.test/v1',
            'services.ai.model' => null,
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('Konfigurasi layanan AI belum lengkap.');

        app(AiProviderService::class)->getContent([
            ['role' => 'user', 'content' => 'Analyze'],
        ]);
    }
}
