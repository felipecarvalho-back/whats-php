<?php

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ApiService;
use App\Services\AuthService;
use App\Services\ChatSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
