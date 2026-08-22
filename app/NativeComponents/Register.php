<?php

namespace App\NativeComponents;

use App\Services\ApiService;
use App\Services\AuthService;
use Exception;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Register extends NativeComponent
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $errorMessage = '';

    public bool $loading = false;

    public bool $showServerConfig = false;

    public string $serverUrl = '';

    public function mount(): void
    {
        $this->serverUrl = app(ApiService::class)->getBaseUrl();
    }

    public function toggleServerConfig(): void
    {
        $this->showServerConfig = ! $this->showServerConfig;
        $this->errorMessage = '';
    }

    public function setPresetUrl(string $url): void
    {
        $this->serverUrl = $url;
        $this->saveServerConfig();
    }

    public function saveServerConfig(): void
    {
        $url = trim($this->serverUrl);
        if (! empty($url)) {
            app(ApiService::class)->setCustomBaseUrl($url);
            $this->serverUrl = app(ApiService::class)->getBaseUrl();
            $this->showServerConfig = false;
            $this->errorMessage = 'Servidor atualizado para: '.$this->serverUrl;
        }
    }

    public function submit(): void
    {
        $this->errorMessage = '';

        if (empty(trim($this->name)) || empty(trim($this->email)) || empty(trim($this->password))) {
            $this->errorMessage = 'Por favor, preencha todos os campos obrigatórios.';

            return;
        }

        $this->loading = true;

        $authService = app(AuthService::class);
        $apiService = app(ApiService::class);

        $cleanUsername = ! empty(trim($this->username)) ? ltrim(strtolower(trim($this->username)), '@') : null;

        try {
            $data = $apiService->register(
                trim($this->name),
                trim($this->email),
                trim($this->password),
                $cleanUsername
            );
            $user = $data['user'] ?? [];

            $authService->saveSession(
                userId: (int) ($user['id'] ?? 1),
                name: (string) ($user['name'] ?? $this->name),
                email: (string) ($user['email'] ?? $this->email),
                token: (string) ($data['token'] ?? 'jwt_token'),
                username: (string) ($user['username'] ?? $cleanUsername)
            );

            $this->replace('/');
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function goToLogin(): void
    {
        $this->navigate('/login');
    }

    public function render(): View
    {
        return view('native.register', [
            'errorMessage' => $this->errorMessage,
            'loading' => $this->loading,
            'showServerConfig' => $this->showServerConfig,
            'serverUrl' => $this->serverUrl,
        ]);
    }
}
