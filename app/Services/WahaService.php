<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WahaService
{
    private string $url;
    private string $apiKey;
    private string $session;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey = (string) config('services.waha.api_key');
        $this->session = (string) config('services.waha.session', 'alakbar');
    }

    public function sessionName(): string
    {
        return $this->session;
    }

    public function session(): ?array
    {
        $response = $this->request()->get("/api/sessions/{$this->session}");

        if ($response->status() === 404) {
            return null;
        }

        return $this->json($response);
    }

    public function account(): ?array
    {
        $response = $this->request()->get("/api/sessions/{$this->session}/me");

        if ($response->status() === 404 || $response->json() === null) {
            return null;
        }

        return $this->json($response);
    }

    public function createSession(): array
    {
        return $this->json($this->request()->post('/api/sessions', ['name' => $this->session]));
    }

    public function start(): array
    {
        return $this->json($this->request()->post("/api/sessions/{$this->session}/start"));
    }

    public function restart(): array
    {
        return $this->json($this->request()->post("/api/sessions/{$this->session}/restart"));
    }

    public function logout(): array
    {
        return $this->json($this->request()->post("/api/sessions/{$this->session}/logout"));
    }

    public function deleteSession(): array
    {
        return $this->json($this->request()->delete("/api/sessions/{$this->session}"));
    }

    public function qr(): array
    {
        $response = $this->request()->acceptJson()->get("/api/{$this->session}/auth/qr?format=image");

        if (!$response->successful()) {
            return $this->json($response);
        }

        $json = $response->json();
        if (is_array($json) && $json !== []) {
            return $json;
        }

        $body = $response->body();
        if ($body === '') {
            return [];
        }

        return [
            'binary' => base64_encode($body),
            'mimetype' => $response->header('Content-Type') ?: 'image/png',
        ];
    }

    public function sendText(string $phone, string $message): array
    {
        return $this->json($this->request()->post('/api/sendText', [
            'session' => $this->session,
            'chatId' => "{$phone}@c.us",
            'text' => $message,
        ]));
    }

    private function request(): PendingRequest
    {
        if ($this->url === '' || $this->apiKey === '') {
            throw new RuntimeException('Konfigurasi WhatsApp Gateway belum lengkap.');
        }

        return Http::baseUrl($this->url)
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => $this->apiKey]);
    }

    private function json(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        throw new RuntimeException('WhatsApp Gateway tidak dapat memproses permintaan.', $response->status());
    }
}
