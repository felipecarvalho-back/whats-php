<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class ConversationCard extends NativeComponent
{
    public ?Conversation $conversation = null;

    public function openChat(): void
    {
        if ($this->conversation) {
            $this->navigate('/chat/'.$this->conversation->id);
        }
    }

    public function render(): View
    {
        return view('native.components.conversation-card', [
            'conversation' => $this->conversation,
            'contact' => $this->conversation?->contact,
        ]);
    }
}
