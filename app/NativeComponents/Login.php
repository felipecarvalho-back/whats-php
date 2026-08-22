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

        if (empty(trim($this->email)) || empty(trim($this->password))) {
            $this->errorMessage = 'Por favor, preencha o e-mail e a senha.';

            return;
        }

        $this->loading = true;

        try {
            $apiService = app(ApiService::class);
            $response = $apiService->login(trim($this->email), trim($this->password));

            $authService = app(AuthService::class);
            $authService->saveSession(
                userId: (int) ($response['user']['id'] ?? 1),
                name: (string) ($response['user']['name'] ?? 'Usuário'),
                email: (string) ($response['user']['email'] ?? $this->email),
                token: (string) ($response['token'] ?? ''),
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
