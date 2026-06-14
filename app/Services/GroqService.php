<?php

namespace App\Services;

use App\Exceptions\GroqException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GroqService
{
    public function chat(array $messages): array
    {
        $apiKey = config('services.groq.api_key');
        $baseUrl = rtrim((string) config('services.groq.base_url'), '/');
        $model = config('services.groq.model');

        if (blank($apiKey)) {
            throw new GroqException('Konfigurasi layanan AI belum lengkap.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->connectTimeout((int) config('services.groq.connect_timeout', 10))
                ->timeout((int) config('services.groq.timeout', 60))
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'max_tokens' => (int) config('services.groq.max_tokens', 2048),
                ]);
        } catch (ConnectionException $exception) {
            throw new GroqException('Layanan AI tidak dapat dihubungi.', previous: $exception);
        }

        if ($response->unauthorized() || $response->forbidden()) {
            throw new GroqException('Konfigurasi autentikasi layanan AI tidak valid.');
        }

        if ($response->status() === 429) {
            throw new GroqException('Batas penggunaan layanan AI sedang tercapai. Coba kembali nanti.');
        }

        if ($response->serverError()) {
            throw new GroqException('Layanan AI sedang mengalami gangguan.');
        }

        if (! $response->successful()) {
            throw new GroqException("Permintaan layanan AI gagal dengan status {$response->status()}.");
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new GroqException('Respons layanan AI tidak dapat dibaca.');
        }

        return $result;
    }

    public function getContent(array $messages): string
    {
        $result = $this->chat($messages);
        $content = data_get($result, 'choices.0.message.content');

        if (! is_string($content) || blank($content)) {
            throw new GroqException('Layanan AI tidak mengembalikan hasil analisis.');
        }

        return $content;
    }
}
