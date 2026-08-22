<?php

namespace App\Services;

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Exception;
use Illuminate\Support\Str;

class ChatSyncService
{
    public function __construct(
        protected ApiService $apiService,
    ) {}

    /**
     * Sincroniza todas as conversas do usuário da API para o SQLite local
     */
    public function syncConversations(): void
    {
        try {
            $remoteList = $this->apiService->getConversations();

            foreach ($remoteList as $remoteItem) {
                $contactData = $remoteItem['contact'] ?? null;
                if (! $contactData) {
                    continue;
                }

                // 1. Cria ou atualiza o contato
                $contact = Contact::query()->updateOrCreate(
                    ['remote_id' => $contactData['id']],
                    [
                        'name' => $contactData['name'] ?? 'Contato',
                        'email' => $contactData['email'] ?? null,
                        'avatar_url' => $contactData['avatarUrl'] ?? null,
                        'status_message' => 'Disponível',
                    ]
                );

                // 2. Cria ou atualiza a conversa local
                $lastMsg = $remoteItem['lastMessage'] ?? null;

                Conversation::query()->updateOrCreate(
                    ['remote_id' => $remoteItem['id']],
                    [
                        'contact_id' => $contact->id,
                        'last_message_content' => $lastMsg['content'] ?? null,
                        'last_message_at' => ! empty($lastMsg['createdAt']) ? $lastMsg['createdAt'] : now(),
                        'unread_count' => (int) ($remoteItem['unreadCount'] ?? 0),
                    ]
                );
            }
        } catch (Exception $e) {
            // Mantém dados do SQLite local em caso de falha de conexão
        }
    }

    /**
     * Garante que uma conversa local possui um remote_id vinculado na API NestJS
     */
    public function ensureRemoteConversation(Conversation $conversation): void
    {
        if ($conversation->remote_id) {
            return;
        }

        $contact = $conversation->contact;
        if (! $contact || ! $contact->remote_id) {
            return;
        }

        try {
            $response = $this->apiService->createConversation($contact->remote_id);
            if (! empty($response['id'])) {
                $conversation->update(['remote_id' => $response['id']]);
            }
        } catch (Exception $e) {
            // Em caso de falha, mantém conversa apenas local
        }
    }

    /**
     * Envia mensagem com atualização otimista imediata no SQLite local e disparo para a API
     */
    public function sendMessage(Conversation $conversation, string $content): Message
    {
        $tempId = 'tmp_'.Str::random(12);

        // 1. Garante remote_id na conversa se o contato tiver remote_id
        if (! $conversation->remote_id && $conversation->contact?->remote_id) {
            $this->ensureRemoteConversation($conversation);
        }

        // 2. Cria mensagem localmente com status pending
        $message = Message::query()->create([
            'temp_id' => $tempId,
            'conversation_id' => $conversation->id,
            'sender_id' => 0, // 0 = Eu mesmo
            'content' => $content,
            'type' => 'text',
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Atualiza a conversa localmente
        $conversation->update([
            'last_message_content' => $content,
            'last_message_at' => now(),
        ]);

        // 3. Tenta sincronizar com o backend
        if ($conversation->remote_id) {
            try {
                $response = $this->apiService->sendMessage($conversation->remote_id, $content, $tempId);
                $message->update([
                    'remote_id' => $response['id'] ?? null,
                    'status' => mb_strtolower($response['status'] ?? 'sent'),
                ]);
            } catch (Exception $e) {
                // Mantém como pending para posterior reenvio
            }
        } else {
            // Em modo offline / demo local, marca como enviado
            $message->update(['status' => 'sent']);
        }

        return $message;
    }

    /**
     * Sincroniza mensagens remotas da conversa para o banco local
     */
    public function syncMessages(Conversation $conversation): void
    {
        if (! $conversation->remote_id && $conversation->contact?->remote_id) {
            $this->ensureRemoteConversation($conversation);
        }

        if (! $conversation->remote_id) {
            return;
        }

        $lastRemoteMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('remote_id')
            ->latest('remote_id')
            ->first();

        $sinceId = $lastRemoteMessage?->remote_id;
        $currentUserId = (int) (AuthSession::current()?->user_id ?? 1);

        try {
            $remoteMessages = $this->apiService->getMessages($conversation->remote_id, $sinceId);

            foreach ($remoteMessages as $remoteMsg) {
                // 1. Procura mensagem local já existente por tempId ou remote_id
                $existing = null;
                if (! empty($remoteMsg['tempId'])) {
                    $existing = Message::query()->where('temp_id', $remoteMsg['tempId'])->first();
                }

                if (! $existing && ! empty($remoteMsg['id'])) {
                    $existing = Message::query()->where('remote_id', $remoteMsg['id'])->first();
                }

                if ($existing) {
                    $existing->update([
                        'remote_id' => $remoteMsg['id'],
                        'status' => mb_strtolower($remoteMsg['status'] ?? 'sent'),
                    ]);

                    continue;
                }

                // 2. Se for nova mensagem
                $isFromMe = ((int) ($remoteMsg['senderId'] ?? 0)) === $currentUserId;

                Message::query()->create([
                    'temp_id' => $remoteMsg['tempId'] ?? null,
                    'remote_id' => $remoteMsg['id'],
                    'conversation_id' => $conversation->id,
                    'sender_id' => $isFromMe ? 0 : ($conversation->contact_id),
                    'content' => $remoteMsg['content'],
                    'type' => mb_strtolower($remoteMsg['type'] ?? 'text'),
                    'status' => mb_strtolower($remoteMsg['status'] ?? 'sent'),
                    'created_at' => ! empty($remoteMsg['createdAt']) ? $remoteMsg['createdAt'] : now(),
                ]);
            }
        } catch (Exception $e) {
            // Silencia falha de rede para manter navegação fluida offline
        }
    }
}
