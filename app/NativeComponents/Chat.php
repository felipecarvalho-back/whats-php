<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatSyncService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class Chat extends NativeComponent
{
    public int $conversationId = 0;

    public string $newMessage = '';

    public function mount(): void
    {
        $this->conversationId = (int) $this->param('id', 0);

        $conversation = $this->conversation;
        if ($conversation) {
            $conversation->update(['unread_count' => 0]);
            app(ChatSyncService::class)->syncMessages($conversation);
        }
    }

    public function goBack(): void
    {
        $this->navigate('/');
    }

    public function sendMessage(): void
    {
        $text = trim($this->newMessage);
        if (empty($text)) {
            return;
        }

        $conversation = $this->conversation;
        if (! $conversation) {
            return;
        }

        app(ChatSyncService::class)->sendMessage($conversation, $text);
        $this->newMessage = '';
    }

    #[Poll(2500)]
    public function pollMessages(): void
    {
        $conversation = $this->conversation;
        if ($conversation) {
            app(ChatSyncService::class)->syncMessages($conversation);
        }
    }

    #[Computed]
    public function conversation(): ?Conversation
    {
        return Conversation::with('contact')->find($this->conversationId);
    }

    /**
     * @return Collection<int, Message>
     */
    #[Computed]
    public function messages(): Collection
    {
        return Message::query()
            ->where('conversation_id', $this->conversationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function render(): View
    {
        $conversation = $this->conversation;
        $contact = $conversation?->contact;

        return view('native.chat', [
            'conversation' => $conversation,
            'contact' => $contact,
            'messages' => $this->messages,
        ]);
    }
}
