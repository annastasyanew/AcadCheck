<?php

namespace App\Services;

use App\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AiProviderService
{
    public function chat(array $messages): array
    {
        $apiKey = config('services.ai.api_key');
        $baseUrl = rtrim((string) config('services.ai.base_url'), '/');
        $model = config('services.ai.model');

        if (blank($apiKey) || blank($baseUrl) || blank($model)) {
            throw new AiProviderException('Konfigurasi layanan AI belum lengkap.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->connectTimeout((int) config('services.ai.connect_timeout', 10))
                ->timeout((int) config('services.ai.timeout', 60))
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'max_tokens' => (int) config('services.ai.max_tokens', 2048),
                ]);
        } catch (ConnectionException $exception) {
            throw new AiProviderException('Layanan AI tidak dapat dihubungi.', previous: $exception);
        }

        if ($response->unauthorized() || $response->forbidden()) {
            throw new AiProviderException('Konfigurasi autentikasi layanan AI tidak valid.');
        }

        if ($response->status() === 429) {
            throw new AiProviderException('Batas penggunaan layanan AI sedang tercapai. Coba kembali nanti.');
        }

        if ($response->serverError()) {
            throw new AiProviderException('Layanan AI sedang mengalami gangguan.');
        }

        if (! $response->successful()) {
            throw new AiProviderException("Permintaan layanan AI gagal dengan status {$response->status()}.");
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new AiProviderException('Respons layanan AI tidak dapat dibaca.');
        }

        return $result;
    }

    public function getContent(array $messages): string
    {
        $result = $this->chat($messages);
        $content = data_get($result, 'choices.0.message.content');

        if (! is_string($content) || blank($content)) {
            throw new AiProviderException('Layanan AI tidak mengembalikan hasil analisis.');
        }

        return $content;
    }
}
