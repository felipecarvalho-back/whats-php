<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use App\Services\ApiService;
use App\Services\AuthService;
use Exception;
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
        }
    }

    public function newChat(): void
    {
        $this->navigate('/contacts');
    }

    public function refreshConversations(): void
    {
        try {
            $apiService = app(ApiService::class);
            $remoteList = $apiService->getConversations();
            foreach ($remoteList as $remoteItem) {
                $conv = Conversation::query()->where('remote_id', $remoteItem['id'])->first();
                if ($conv) {
                    $conv->update([
                        'last_message_content' => $remoteItem['lastMessage']['content'] ?? $conv->last_message_content,
                        'last_message_at' => $remoteItem['lastMessage']['createdAt'] ?? $conv->last_message_at,
                        'unread_count' => $remoteItem['unreadCount'] ?? $conv->unread_count,
                    ]);
                }
            }
        } catch (Exception $e) {
            // Mantém dados do SQLite local
        }
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
