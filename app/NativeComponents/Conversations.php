<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use App\Services\AuthService;
use App\Services\ChatSyncService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class Conversations extends NativeComponent
{
    public function mount(): void
    {
        if (! app(AuthService::class)->isAuthenticated()) {
            $this->replace('/login');

            return;
        }

        $this->refreshConversations();
    }

    public function newChat(): void
    {
        $this->navigate('/contacts');
    }

    public function goToRequests(): void
    {
        $this->navigate('/requests');
    }

    public function logout(): void
    {
        app(AuthService::class)->logout();
        $this->replace('/login');
    }

    #[Poll(3000)]
    public function refreshConversations(): void
    {
        $syncService = app(ChatSyncService::class);
        $syncService->syncConversations();
        $syncService->syncPendingRequests();
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        return Conversation::query()
            ->where('status', 'ACCEPTED')
            ->with('contact')
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    #[Computed]
    public function pendingRequestsCount(): int
    {
        return Conversation::query()
            ->where('status', 'PENDING')
            ->count();
    }

    public function render(): View
    {
        return view('native.conversations', [
            'conversations' => $this->conversations,
            'pendingRequestsCount' => $this->pendingRequestsCount,
        ]);
    }
}
