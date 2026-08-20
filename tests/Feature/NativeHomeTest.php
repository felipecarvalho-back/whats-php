<?php

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    Http::fake([
        '*' => Http::response([], 200),
    ]);
});

it('renders the conversations screen with native theme tokens and accessibility', function () {
    AuthSession::query()->create([
        'user_id' => 1,
        'name' => 'Usuário Teste',
        'email' => 'teste@example.com',
        'token' => 'token123',
        'is_active' => true,
    ]);

    $contact = Contact::firstOrCreate(['name' => 'Mariana Souza'], ['email' => 'mariana@example.com']);
    Conversation::firstOrCreate(['contact_id' => $contact->id], [
        'last_message_content' => 'Olá!',
        'last_message_at' => now(),
        'unread_count' => 1,
    ]);

    $screen = Native::visit('/');

    $screen->assertSee('WhatsApp')
        ->assertSee('Mariana Souza');

    $screen->assertAccessible();
});

it('renders the login screen with accessibility', function () {
    $screen = Native::visit('/login');

    $screen->assertSee('WhatsApp Native')
        ->assertSee('Cadastre-se');

    $screen->assertAccessible();
});
