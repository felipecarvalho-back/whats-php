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

    public string $email = '';

    public string $password = '';

    public string $errorMessage = '';

    public bool $loading = false;

    public function submit(): void
    {
        $this->errorMessage = '';

        if (empty(trim($this->name)) || empty(trim($this->email)) || empty(trim($this->password))) {
            $this->errorMessage = 'Por favor, preencha todos os campos.';

            return;
        }

        $this->loading = true;

        $authService = app(AuthService::class);
        $apiService = app(ApiService::class);

        try {
            $data = $apiService->register(trim($this->name), trim($this->email), trim($this->password));
            $user = $data['user'] ?? [];

            $authService->saveSession(
                (int) ($user['id'] ?? 1),
                (string) ($user['name'] ?? $this->name),
                (string) ($user['email'] ?? $this->email),
                (string) ($data['token'] ?? 'jwt_token')
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
        ]);
    }
}
