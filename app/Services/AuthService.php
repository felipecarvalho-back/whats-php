<?php

namespace App\Services;

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

class AuthService
{
    public function currentSession(): ?AuthSession
    {
        return AuthSession::current();
    }

    public function isAuthenticated(): bool
    {
        return $this->currentSession() !== null;
    }

    public function token(): ?string
    {
        return $this->currentSession()?->token;
    }

    public function currentUserId(): ?int
    {
        return $this->currentSession()?->user_id;
    }

    public function saveSession(int $userId, string $name, string $email, string $token, ?string $username = null): AuthSession
    {
        // Remove qualquer sessão anterior para garantir sessão única
        AuthSession::query()->delete();

        return AuthSession::query()->create([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'token' => $token,
            'is_active' => true,
        ]);
    }

    public function logout(): void
    {
        // Exclui completamente as sessões ativas do SQLite local
        AuthSession::query()->delete();

        // Limpa o cache local de mensagens, conversas e contatos para privacidade
        Message::query()->delete();
        Conversation::query()->delete();
        Contact::query()->delete();
    }
}
