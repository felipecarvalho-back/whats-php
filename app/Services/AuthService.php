<?php

namespace App\Services;

use App\Models\AuthSession;

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

    public function saveSession(int $userId, string $name, string $email, string $token): AuthSession
    {
        // Desativa sessões antigas
        AuthSession::query()->where('is_active', true)->update(['is_active' => false]);

        return AuthSession::query()->create([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'token' => $token,
            'is_active' => true,
        ]);
    }

    public function logout(): void
    {
        AuthSession::query()->where('is_active', true)->update(['is_active' => false]);
    }
}
