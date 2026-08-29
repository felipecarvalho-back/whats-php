<?php

namespace App\NativeComponents;

use App\Services\ApiService;
use App\Services\AuthService;
use Exception;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Login extends NativeComponent
{
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

        $identifier = trim($this->email);
        $password = trim($this->password);

        if (empty($identifier) || empty($password)) {
            $this->errorMessage = 'Por favor, preencha o e-mail/usuário e a senha.';

            return;
        }

        $this->loading = true;

        try {
            $apiService = app(ApiService::class);

            // Suporte automático a login por @username ou por E-mail
            $emailToUse = $identifier;
            if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $cleanUsername = ltrim($identifier, '@');
                $userProfile = $apiService->getUserByUsername($cleanUsername);
                if ($userProfile && ! empty($userProfile['email'])) {
                    $emailToUse = $userProfile['email'];
                }
            }

            $response = $apiService->login($emailToUse, $password);
            $user = $response['user'] ?? [];

            $authService = app(AuthService::class);
            $authService->saveSession(
                userId: (int) ($user['id'] ?? 1),
                name: (string) ($user['name'] ?? 'Usuário'),
                email: (string) ($user['email'] ?? $emailToUse),
                token: (string) ($response['token'] ?? ''),
                username: (string) ($user['username'] ?? ltrim($identifier, '@'))
            );

            $this->replace('/');
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function goToRegister(): void
    {
        $this->navigate('/register');
    }

    public function render(): View
    {
        return view('native.login', [
            'errorMessage' => $this->errorMessage,
            'loading' => $this->loading,
            'showServerConfig' => $this->showServerConfig,
            'serverUrl' => $this->serverUrl,
        ]);
    }
}
