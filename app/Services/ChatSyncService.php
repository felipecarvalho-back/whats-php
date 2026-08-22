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
     * Sincroniza todas as conversas aceitas do usuário da API para o SQLite local
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
                        'username' => $contactData['username'] ?? null,
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
                        'status' => strtoupper($remoteItem['status'] ?? 'ACCEPTED'),
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
     * Sincroniza solicitações de conversa pendentes (Instagram Direct style)
     */
    public function syncPendingRequests(): int
    {
        try {
            $data = $this->apiService->getPendingRequests();
            $requests = $data['requests'] ?? [];

            foreach ($requests as $req) {
                $senderData = $req['sender'] ?? null;
                if (! $senderData) {
                    continue;
                }

                // 1. Cria ou atualiza o contato remetente
                $contact = Contact::query()->updateOrCreate(
                    ['remote_id' => $senderData['id']],
                    [
                        'name' => $senderData['name'] ?? 'Contato',
                        'email' => $senderData['email'] ?? null,
                        'username' => $senderData['username'] ?? null,
                        'avatar_url' => $senderData['avatarUrl'] ?? null,
                        'status_message' => 'Solicitação de mensagem',
                    ]
                );

                // 2. Cria ou atualiza a conversa com status PENDING
                $initialMsg = $req['initialMessage'] ?? null;

                $conversation = Conversation::query()->updateOrCreate(
                    ['remote_id' => $req['id']],
                    [
                        'contact_id' => $contact->id,
                        'status' => 'PENDING',
                        'initiated_by_id' => (int) ($senderData['id'] ?? 0),
                        'last_message_content' => $initialMsg['content'] ?? null,
                        'last_message_at' => ! empty($initialMsg['createdAt']) ? $initialMsg['createdAt'] : now(),
                        'unread_count' => 1,
                    ]
                );

                // 3. Salva a mensagem inicial se existir
                if ($initialMsg && ! empty($initialMsg['id'])) {
                    Message::query()->updateOrCreate(
                        ['remote_id' => $initialMsg['id']],
                        [
                            'conversation_id' => $conversation->id,
                            'sender_id' => $contact->id,
                            'content' => $initialMsg['content'],
                            'type' => 'text',
                            'status' => mb_strtolower($initialMsg['status'] ?? 'delivered'),
                            'created_at' => ! empty($initialMsg['createdAt']) ? $initialMsg['createdAt'] : now(),
                        ]
                    );
                }
            }

            return (int) ($data['totalPending'] ?? count($requests));
        } catch (Exception $e) {
            return 0;
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
