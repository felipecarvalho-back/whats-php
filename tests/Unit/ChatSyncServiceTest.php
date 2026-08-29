<?php

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ApiService;
use App\Services\AuthService;
use App\Services\ChatSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('saves and retrieves active auth session', function () {
    $auth = new AuthService;
    $session = $auth->saveSession(99, 'Carlos', 'carlos@test.com', 'jwt_secret_token');

    expect($auth->isAuthenticated())->toBeTrue()
        ->and($auth->currentSession()?->user_id)->toBe(99)
        ->and($auth->token())->toBe('jwt_secret_token');

    $auth->logout();
    expect($auth->isAuthenticated())->toBeFalse();
});

it('sends message optimistically to local sqlite', function () {
    $contact = Contact::firstOrCreate(['name' => 'Contato Teste'], ['email' => 'contato@test.com']);
    $conversation = Conversation::firstOrCreate(['contact_id' => $contact->id], [
        'last_message_content' => null,
        'last_message_at' => now(),
    ]);

    $auth = new AuthService;
    $api = new ApiService($auth);
    $sync = new ChatSyncService($api);

    $msg = $sync->sendMessage($conversation, 'Minha mensagem offline');

    expect($msg)->toBeInstanceOf(Message::class)
        ->and($msg->content)->toBe('Minha mensagem offline')
        ->and($msg->isOutgoing())->toBeTrue()
        ->and($conversation->fresh()->last_message_content)->toBe('Minha mensagem offline');
});

it('syncs conversations from remote API into local SQLite', function () {
    Http::fake([
        '*/api/conversations' => Http::response([
            [
                'id' => 10,
                'isGroup' => false,
                'contact' => [
                    'id' => 2,
                    'name' => 'Mariana Remota',
                    'email' => 'mariana.remota@example.com',
                    'avatarUrl' => null,
                ],
                'lastMessage' => [
                    'id' => 45,
                    'content' => 'Mensagem vinda da API',
                    'senderId' => 2,
                    'status' => 'DELIVERED',
                    'createdAt' => '2026-08-21T22:00:00.000Z',
                ],
                'unreadCount' => 3,
                'updatedAt' => '2026-08-21T22:00:00.000Z',
            ],
        ], 200),
    ]);

    $auth = new AuthService;
    $api = new ApiService($auth);
    $sync = new ChatSyncService($api);

    $sync->syncConversations();

    $contact = Contact::where('remote_id', 2)->first();
    expect($contact)->not->toBeNull()
        ->and($contact->name)->toBe('Mariana Remota');

    $conversation = Conversation::where('remote_id', 10)->first();
    expect($conversation)->not->toBeNull()
        ->and($conversation->last_message_content)->toBe('Mensagem vinda da API')
        ->and($conversation->unread_count)->toBe(3);
});

it('syncs remote messages and matches incoming vs outgoing correctly', function () {
    $auth = new AuthService;
    $auth->saveSession(1, 'Carlos', 'carlos@test.com', 'token123');

    $contact = Contact::create(['remote_id' => 2, 'name' => 'Mariana']);
    $conversation = Conversation::create(['remote_id' => 10, 'contact_id' => $contact->id]);

    Http::fake([
        '*/api/conversations/10/messages*' => Http::response([
            [
                'id' => 101,
                'tempId' => null,
                'conversationId' => 10,
                'senderId' => 1, // Eu (current user)
                'content' => 'Oi Mariana!',
                'type' => 'TEXT',
                'status' => 'READ',
                'createdAt' => '2026-08-21T21:59:00.000Z',
            ],
            [
                'id' => 102,
                'tempId' => null,
                'conversationId' => 10,
                'senderId' => 2, // Mariana (contato)
                'content' => 'Oi Carlos!',
                'type' => 'TEXT',
                'status' => 'DELIVERED',
                'createdAt' => '2026-08-21T22:00:00.000Z',
            ],
        ], 200),
    ]);

    $api = new ApiService($auth);
    $sync = new ChatSyncService($api);

    $sync->syncMessages($conversation);

    $msg1 = Message::where('remote_id', 101)->first();
    $msg2 = Message::where('remote_id', 102)->first();

    expect($msg1)->not->toBeNull()
        ->and($msg1->sender_id)->toBe(0) // Outgoing (eu)
        ->and($msg1->isOutgoing())->toBeTrue()
        ->and($msg1->status)->toBe('read');

    expect($msg2)->not->toBeNull()
        ->and($msg2->sender_id)->toBe($contact->id) // Incoming (Mariana)
        ->and($msg2->isOutgoing())->toBeFalse()
        ->and($msg2->status)->toBe('delivered');
});

it('marks conversation and received messages as read in local SQLite and notifies API', function () {
    $contact = Contact::create(['remote_id' => 3, 'name' => 'Lucas']);
    $conversation = Conversation::create([
        'remote_id' => 25,
        'contact_id' => $contact->id,
        'unread_count' => 2,
    ]);

    $incomingMsg1 = Message::create([
        'remote_id' => 201,
        'conversation_id' => $conversation->id,
        'sender_id' => $contact->id,
        'content' => 'Mensagem 1',
        'type' => 'text',
        'status' => 'delivered',
    ]);

    $incomingMsg2 = Message::create([
        'remote_id' => 202,
        'conversation_id' => $conversation->id,
        'sender_id' => $contact->id,
        'content' => 'Mensagem 2',
        'type' => 'text',
        'status' => 'sent',
    ]);

    Http::fake([
        '*/api/conversations/25/read' => Http::response(['success' => true, 'markedCount' => 2], 200),
    ]);

    $auth = new AuthService;
    $api = new ApiService($auth);
    $sync = new ChatSyncService($api);

    $sync->markConversationAsRead($conversation);

    expect($conversation->fresh()->unread_count)->toBe(0)
        ->and($incomingMsg1->fresh()->status)->toBe('read')
        ->and($incomingMsg2->fresh()->status)->toBe('read');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/conversations/25/read'));
});
