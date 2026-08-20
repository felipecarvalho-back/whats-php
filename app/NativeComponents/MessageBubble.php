<?php

namespace App\NativeComponents;

use App\Models\Message;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class MessageBubble extends NativeComponent
{
    public ?Message $message = null;

    public function render(): View
    {
        return view('native.components.message-bubble', [
            'message' => $this->message,
            'isOutgoing' => $this->message?->isOutgoing() ?? true,
        ]);
    }
}
