<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ApiService
{
    public function __construct(
        protected AuthService $authService,
        protected ?string $baseUrl = null,
    ) {
        $this->baseUrl = $baseUrl ?: (string) config('services.chat_api.base_url', 'http://10.0.2.2:3000');
    }

    protected function client(): PendingRequest
    {
        $req = Http::baseUrl($this->baseUrl)
            ->timeout(10)
            ->acceptJson();

        $token = $this->authService->token();
        if ($token) {
            $req->withToken($token);
        }

        return $req;
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        $response = $this->client()->post('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw new Exception($response->json('message') ?? 'Falha ao autenticar.');
        }

        return $response->json();
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function register(string $name, string $email, string $password): array
    {
        $response = $this->client()->post('/api/auth/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw new Exception($response->json('message') ?? 'Falha ao registrar usuário.');
        }

        return $response->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConversations(): array
    {
        $response = $this->client()->get('/api/conversations');

        return $response->successful() ? $response->json() : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(int $conversationId, ?int $sinceId = null): array
    {
        $params = $sinceId ? ['since_id' => $sinceId] : [];
        $response = $this->client()->get("/api/conversations/{$conversationId}/messages", $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(int $conversationId, string $content, string $tempId): array
    {
        $response = $this->client()->post("/api/conversations/{$conversationId}/messages", [
            'tempId' => $tempId,
            'content' => $content,
            'type' => 'TEXT',
        ]);

        if (! $response->successful()) {
            throw new Exception($response->json('message') ?? 'Erro ao enviar mensagem.');
        }

        return $response->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContacts(): array
    {
        $response = $this->client()->get('/api/users/contacts');

        return $response->successful() ? $response->json() : [];
    }
}
