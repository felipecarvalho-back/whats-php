<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\Network;
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

    /**
     * Verifica se o dispositivo possui conexão de rede ativa
     */
    public function isConnected(): bool
    {
        try {
            if (class_exists(Network::class)) {
                $status = Network::status();
                if ($status && isset($status->connected)) {
                    return (bool) $status->connected;
                }
            }
        } catch (Throwable $e) {
            // Silencia falhas e assume conectado para testes / dev
        }

        return true;
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
    public function register(string $name, string $email, string $password, ?string $username = null, ?string $avatarUrl = null): array
    {
        try {
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ];

            if ($username) {
                $payload['username'] = ltrim(strtolower(trim($username)), '@');
            }

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
     * Marcar todas as mensagens de uma conversa como lidas na API NestJS
     *
     * @return array<string, mixed>
     */
    public function markConversationAsRead(int $conversationId): array
    {
        try {
            $response = $this->client()->patch("/conversations/{$conversationId}/read");

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            return [];
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
     * Buscar usuários por nome ou @username
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchUsers(string $query): array
    {
        try {
            $cleanQuery = ltrim(trim($query), '@');
            if (empty($cleanQuery)) {
                return [];
            }

            $response = $this->client()->get('/users/search', [
                'q' => $cleanQuery,
            ]);

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Buscar dados de um usuário pelo @username
     *
     * @return array<string, mixed>|null
     */
    public function getUserByUsername(string $username): ?array
    {
        try {
            $cleanUsername = ltrim(trim($username), '@');
            $response = $this->client()->get("/users/by-username/{$cleanUsername}");

            return $response->successful() ? $response->json() : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Enviar solicitação de mensagem / mensagem inicial no estilo Instagram Direct
     *
     * @return array<string, mixed>
     */
    public function sendConversationRequest(string $recipientUsername, string $content, ?string $tempId = null): array
    {
        try {
            $cleanUsername = ltrim(trim($recipientUsername), '@');
            $response = $this->client()->post('/conversations/request', [
                'recipientUsername' => $cleanUsername,
                'content' => $content,
                'tempId' => $tempId ?: 'tmp_'.time().'_'.uniqid(),
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
     * Listar solicitações de mensagem pendentes recebidas
     *
     * @return array{totalPending: int, requests: array<int, array<string, mixed>>}
     */
    public function getPendingRequests(): array
    {
        try {
            $response = $this->client()->get('/conversations/requests');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'totalPending' => (int) ($data['totalPending'] ?? count($data['requests'] ?? [])),
                    'requests' => (array) ($data['requests'] ?? []),
                ];
            }

            return ['totalPending' => 0, 'requests' => []];
        } catch (Throwable $e) {
            return ['totalPending' => 0, 'requests' => []];
        }
    }

    /**
     * Aceitar solicitação de conversa
     *
     * @return array<string, mixed>
     */
    public function acceptConversation(int $conversationId): array
    {
        try {
            $response = $this->client()->patch("/conversations/{$conversationId}/accept");

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * Recusar / excluir solicitação de conversa
     *
     * @return array<string, mixed>
     */
    public function rejectConversation(int $conversationId): array
    {
        try {
            $response = $this->client()->patch("/conversations/{$conversationId}/reject");

            if (! $response->successful()) {
                throw new Exception($this->extractErrorMessage($response));
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->handleNetworkException($e);
        }
    }

    /**
     * Bloquear usuário
     *
     * @return array<string, mixed>
     */
    public function blockUser(int $userId): array
    {
        try {
            $response = $this->client()->post("/users/{$userId}/block");

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
