<?php

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sessão do usuário atual (demo)
        if (AuthSession::query()->count() === 0) {
            AuthSession::query()->create([
                'user_id' => 1,
                'name' => 'Meu Perfil',
                'email' => 'usuario@example.com',
                'token' => 'demo_jwt_token_for_local_testing',
                'is_active' => true,
            ]);
        }

        // Contatos demo
        if (Contact::query()->count() === 0) {
            $c1 = Contact::query()->create([
                'remote_id' => 2,
                'name' => 'Mariana Souza',
                'email' => 'mariana@example.com',
                'avatar_url' => null,
                'status_message' => 'Disponível',
                'last_seen_at' => now()->subMinutes(5),
            ]);

            $c2 = Contact::query()->create([
                'remote_id' => 3,
                'name' => 'Lucas Rocha',
                'email' => 'lucas@example.com',
                'avatar_url' => null,
                'status_message' => 'No trabalho',
                'last_seen_at' => now()->subHours(1),
            ]);

            $c3 = Contact::query()->create([
                'remote_id' => 4,
                'name' => 'Beatriz Lima',
                'email' => 'beatriz@example.com',
                'avatar_url' => null,
                'status_message' => 'Online',
                'last_seen_at' => now(),
            ]);

            // Conversas demo
            $conv1 = Conversation::query()->create([
                'remote_id' => 101,
                'contact_id' => $c1->id,
                'last_message_content' => 'Combinado! Te vejo mais tarde.',
                'last_message_at' => now()->subMinutes(12),
                'unread_count' => 1,
            ]);

            $conv2 = Conversation::query()->create([
                'remote_id' => 102,
                'contact_id' => $c2->id,
                'last_message_content' => 'Você viu o novo projeto em NativePHP?',
                'last_message_at' => now()->subHours(2),
                'unread_count' => 0,
            ]);

            $conv3 = Conversation::query()->create([
                'remote_id' => 103,
                'contact_id' => $c3->id,
                'last_message_content' => 'Oi! Tudo bem?',
                'last_message_at' => now()->subDays(1),
                'unread_count' => 0,
            ]);

            // Mensagens da conversa 1
            Message::query()->create([
                'conversation_id' => $conv1->id,
                'sender_id' => 0, // Eu
                'content' => 'Oi Mariana, tudo certo para a reunião de hoje?',
                'type' => 'text',
                'status' => 'read',
                'created_at' => now()->subMinutes(25),
            ]);

            Message::query()->create([
                'conversation_id' => $conv1->id,
                'sender_id' => $c1->id, // Mariana
                'content' => 'Tudo certo sim! Vamos apresentar a arquitetura.',
                'type' => 'text',
                'status' => 'read',
                'created_at' => now()->subMinutes(20),
            ]);

            Message::query()->create([
                'conversation_id' => $conv1->id,
                'sender_id' => 0, // Eu
                'content' => 'Perfeito, vou preparar a demonstração do app.',
                'type' => 'text',
                'status' => 'read',
                'created_at' => now()->subMinutes(15),
            ]);

            Message::query()->create([
                'conversation_id' => $conv1->id,
                'sender_id' => $c1->id, // Mariana
                'content' => 'Combinado! Te vejo mais tarde.',
                'type' => 'text',
                'status' => 'delivered',
                'created_at' => now()->subMinutes(12),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Message::query()->truncate();
        Conversation::query()->truncate();
        Contact::query()->truncate();
        AuthSession::query()->truncate();
    }
};
