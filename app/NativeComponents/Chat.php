<?php

namespace App\NativeComponents;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ApiService;
use App\Services\ChatSyncService;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class Chat extends NativeComponent
{
    public int $conversationId = 0;

    public string $newMessage = '';

    public string $actionFeedback = '';

    public function mount(): void
    {
        $this->conversationId = (int) $this->param('id', 0);

        $conversation = $this->conversation;
        if ($conversation) {
            $syncService = app(ChatSyncService::class);
            $syncService->markConversationAsRead($conversation);
            $syncService->syncMessages($conversation);
        }
    }

    public function goBack(): void
    {
        $this->navigate('/');
    }

    public function acceptRequest(): void
    {
        $conversation = $this->conversation;
        if (! $conversation) {
            return;
        }

        try {
            if ($conversation->remote_id) {
                app(ApiService::class)->acceptConversation($conversation->remote_id);
            }
            $conversation->update(['status' => 'ACCEPTED']);
            $this->actionFeedback = 'Solicitação aceita! Conversa liberada.';
        } catch (Exception $e) {
            // Em caso de falha de rede, aceita localmente
            $conversation->update(['status' => 'ACCEPTED']);
        }
    }

    public function rejectRequest(): void
    {
        $conversation = $this->conversation;
        if (! $conversation) {
            return;
        }

        try {
            if ($conversation->remote_id) {
                app(ApiService::class)->rejectConversation($conversation->remote_id);
            }
            $conversation->update(['status' => 'REJECTED']);
            $this->replace('/requests');
        } catch (Exception $e) {
            $conversation->update(['status' => 'REJECTED']);
            $this->replace('/requests');
        }
    }

    public function blockContact(): void
    {
        $conversation = $this->conversation;
        if (! $conversation) {
            return;
        }

        $contact = $conversation->contact;
        if ($contact && $contact->remote_id) {
            try {
                app(ApiService::class)->blockUser($contact->remote_id);
            } catch (Exception $e) {
                // Silencia falha
            }
        }

        $conversation->update(['status' => 'REJECTED']);
        $this->replace('/');
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
            $syncService = app(ChatSyncService::class);
            $syncService->syncMessages($conversation);
            $syncService->markConversationAsRead($conversation);
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

    /**
     * Agrupa as mensagens por data para exibição de divisores ("Hoje", "Ontem", "27 de agosto", etc.)
     *
     * @return array<int, array{date_label: string, messages: Collection<int, Message>}>
     */
    #[Computed]
    public function groupedMessages(): array
    {
        $allMessages = $this->messages;
        if ($allMessages->isEmpty()) {
            return [];
        }

        $grouped = [];
        $byDate = $allMessages->groupBy(function (Message $message) {
            return $message->created_at ? $message->created_at->format('Y-m-d') : 'unknown';
        });

        foreach ($byDate as $msgs) {
            $first = $msgs->first();
            $grouped[] = [
                'date_label' => $this->formatDateLabel($first?->created_at),
                'messages' => $msgs,
            ];
        }

        return $grouped;
    }

    protected function formatDateLabel(?CarbonInterface $date): string
    {
        if (! $date) {
            return 'Hoje';
        }

        if ($date->isToday()) {
            return 'Hoje';
        }

        if ($date->isYesterday()) {
            return 'Ontem';
        }

        $months = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
        ];

        $day = $date->format('j');
        $month = $months[(int) $date->format('n')] ?? $date->format('F');

        if ($date->isCurrentYear()) {
            return "{$day} de {$month}";
        }

        return "{$day} de {$month} de {$date->format('Y')}";
    }

    public function render(): View
    {
        $conversation = $this->conversation;
        $contact = $conversation?->contact;

        return view('native.chat', [
            'conversation' => $conversation,
            'contact' => $contact,
            'messages' => $this->messages,
            'groupedMessages' => $this->groupedMessages,
            'actionFeedback' => $this->actionFeedback,
        ]);
    }
}
