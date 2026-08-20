<?php

namespace App\Services;

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
     * Envia mensagem com atualização otimista imediata no SQLite local
     */
    public function sendMessage(Conversation $conversation, string $content): Message
    {
        $tempId = 'tmp_'.Str::random(12);

        // 1. Cria mensagem localmente com status pending
        $message = Message::query()->create([
            'temp_id' => $tempId,
            'conversation_id' => $conversation->id,
            'sender_id' => 0, // Eu
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

        // 2. Tenta sincronizar com o backend se a conversa tiver remote_id
        if ($conversation->remote_id) {
            try {
                $response = $this->apiService->sendMessage($conversation->remote_id, $content, $tempId);
                $message->update([
                    'remote_id' => $response['id'] ?? null,
                    'status' => 'sent',
                ]);
            } catch (Exception $e) {
                // Em caso de falha de conexão, permanece como pending para reenvio
            }
        } else {
            // Em modo offline / demo local, marca como enviado
            $message->update(['status' => 'sent']);
        }

        return $message;
    }

    /**
     * Sincroniza mensagens remotas para o banco local
     */
    public function syncMessages(Conversation $conversation): void
    {
        if (! $conversation->remote_id) {
            return;
        }

        $lastRemoteMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('remote_id')
            ->latest('remote_id')
            ->first();

        $sinceId = $lastRemoteMessage?->remote_id;

        try {
            $remoteMessages = $this->apiService->getMessages($conversation->remote_id, $sinceId);

            foreach ($remoteMessages as $remoteMsg) {
                // Evita duplicar se já foi inserida pelo tempId
                $existing = null;
                if (! empty($remoteMsg['tempId'])) {
                    $existing = Message::query()->where('temp_id', $remoteMsg['tempId'])->first();
                }

                if ($existing) {
                    $existing->update([
                        'remote_id' => $remoteMsg['id'],
                        'status' => mb_strtolower($remoteMsg['status'] ?? 'sent'),
                    ]);

                    continue;
                }

                $isFromMe = ($remoteMsg['senderId'] ?? 0) === ($conversation->contact?->remote_id ? 0 : 1);

                Message::query()->create([
                    'remote_id' => $remoteMsg['id'],
                    'conversation_id' => $conversation->id,
                    'sender_id' => $isFromMe ? 0 : ($conversation->contact_id),
                    'content' => $remoteMsg['content'],
                    'type' => mb_strtolower($remoteMsg['type'] ?? 'text'),
                    'status' => mb_strtolower($remoteMsg['status'] ?? 'sent'),
                    'created_at' => $remoteMsg['createdAt'] ?? now(),
                ]);
            }
        } catch (Exception $e) {
            // Silencia falha de rede para manter navegação fluida offline
        }
    }
}
