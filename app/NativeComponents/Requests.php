<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use App\Services\ChatSyncService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class Requests extends NativeComponent
{
    public function mount(): void
    {
        $this->refreshRequests();
    }

    #[Poll(3500)]
    public function refreshRequests(): void
    {
        app(ChatSyncService::class)->syncPendingRequests();
    }

    public function openRequest(int $conversationId): void
    {
        $this->navigate('/chat/'.$conversationId);
    }

    public function goBack(): void
    {
        $this->replace('/');
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function pendingRequests(): Collection
    {
        return Conversation::query()
            ->where('status', 'PENDING')
            ->with('contact')
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    public function render(): View
    {
        return view('native.requests', [
            'pendingRequests' => $this->pendingRequests,
        ]);
    }
}
