<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class ApiService
{
    public function __construct(
        protected AuthService $authService,
        protected ?string $baseUrl = null,
    ) {
        $this->baseUrl = $this->resolveBaseUrl($baseUrl);
    }

    public function resolveBaseUrl(?string $explicitUrl = null): string
    {
        $url = $explicitUrl ?: Cache::get('custom_chat_api_url');
        if (! $url) {
            $url = (string) config('services.chat_api.base_url', 'http://127.0.0.1:3000/api');
        }

        $url = rtrim($url, '/');
        if (! str_ends_with($url, '/api')) {
            $url .= '/api';
        }

        return $url;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl ?: $this->resolveBaseUrl();
    }

    public function setCustomBaseUrl(string $url): void
    {
        $url = rtrim(trim($url), '/');
        if (! empty($url)) {
            if (! str_ends_with($url, '/api')) {
                $url .= '/api';
            }
            Cache::forever('custom_chat_api_url', $url);
            $this->baseUrl = $url;
        }
    }

    protected function client(): PendingRequest
    {
        $req = Http::baseUrl($this->getBaseUrl())
            ->timeout(6)
            ->acceptJson();

        $token = $this->authService->token();
        if ($token) {
            $req->withToken($token);
        }

        return $req;
    }

    protected function handleNetworkException(Throwable $e): never
    {
        $msg = $e->getMessage();
        $currentUrl = $this->getBaseUrl();

        if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'timed out') || str_contains($msg, 'timeout')) {
            throw new Exception("Não foi possível conectar ao servidor ({$currentUrl}). Verifique se o backend NestJS está ligado e se o IP está correto para sua rede.");
        }

        if (str_contains($msg, 'cURL error 7') || str_contains($msg, 'Connection refused') || str_contains($msg, 'Failed to connect')) {
            throw new Exception("Conexão recusada em {$currentUrl}. Verifique se o servidor NestJS está rodando.");
        }

        throw new Exception($msg);
    }

    protected function extractErrorMessage(mixed $response): string
    {
        $json = $response->json();
        if (isset($json['message'])) {
            if (is_array($json['message'])) {
                return implode(', ', $json['message']);
            }

            return (string) $json['message'];
        }

        return 'Erro na comunicação com o servidor.';
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = $this->client()->post('/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function register(string $name, string $email, string $password, ?string $avatarUrl = null): array
    {
        try {
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ];

            if ($avatarUrl) {
                $payload['avatarUrl'] = $avatarUrl;
            }

            $response = $this->client()->post('/auth/register', $payload);

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConversations(): array
    {
        try {
            $response = $this->client()->get('/conversations');

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createConversation(int $recipientUserId): array
    {
        try {
            $response = $this->client()->post('/conversations', [
                'recipientUserId' => $recipientUserId,
            ]);

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(int $conversationId, ?int $sinceId = null): array
    {
        try {
            $params = $sinceId ? ['since_id' => $sinceId] : [];
            $response = $this->client()->get("/conversations/{$conversationId}/messages", $params);

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(int $conversationId, string $content, string $tempId, string $type = 'TEXT'): array
    {
        try {
            $response = $this->client()->post("/conversations/{$conversationId}/messages", [
                'tempId' => $tempId,
                'content' => $content,
                'type' => $type,
            ]);

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function updateMessageStatus(int $messageId, string $status = 'READ'): array
    {
        try {
            $response = $this->client()->patch("/messages/{$messageId}/status", [
                'status' => $status,
            ]);

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContacts(): array
    {
        try {
            $response = $this->client()->get('/users/contacts');

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function registerFcmToken(string $fcmToken): array
    {
        try {
            $response = $this->client()->post('/users/fcm-token', [
                'fcmToken' => $fcmToken,
            ]);

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
