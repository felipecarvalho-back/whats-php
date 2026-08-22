<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use App\Services\AuthService;
use App\Services\ChatSyncService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Edge\NativeComponent;

class Conversations extends NativeComponent
{
    public function mount(): void
    {
        if (! app(AuthService::class)->isAuthenticated()) {
            $this->replace('/login');

            return;
        }

        app(ChatSyncService::class)->syncConversations();
    }

    public function newChat(): void
    {
        $this->navigate('/contacts');
    }

    public function logout(): void
    {
        app(AuthService::class)->logout();
        $this->replace('/login');
    }

    public function refreshConversations(): void
    {
        app(ChatSyncService::class)->syncConversations();
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        return Conversation::with('contact')
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    public function render(): View
    {
        return view('native.conversations', [
            'conversations' => $this->conversations,
        ]);
    }
}
