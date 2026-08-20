<?php

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\NativeComponents\Chat;
use App\NativeComponents\Login;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    Http::fake([
        '*/api/auth/login' => Http::response(['token' => 'fake_jwt_token', 'user' => ['id' => 1, 'name' => 'Usuário Teste', 'email' => 'usuario@example.com']], 200),
        '*/api/auth/register' => Http::response(['token' => 'fake_jwt_token', 'user' => ['id' => 1, 'name' => 'Usuário Teste', 'email' => 'usuario@example.com']], 201),
        '*/api/conversations' => Http::response([], 200),
        '*/api/conversations/*/messages' => Http::response(['id' => 999, 'status' => 'SENT'], 201),
        '*/api/users/contacts' => Http::response([], 200),
        '*' => Http::response([], 200),
    ]);
});

it('renders the login screen with native inputs and button', function () {
    Native::visit('/login')
        ->assertSee('WhatsApp Native')
        ->assertSee('Digite suas credenciais para entrar')
        ->assertSee('Cadastre-se');
});

it('logs in successfully and redirects to conversations', function () {
    Native::test(Login::class)
        ->set('email', 'usuario@example.com')
        ->set('password', '123456')
        ->call('submit');

    expect(AuthSession::current())->not->toBeNull()
        ->and(AuthSession::current()?->email)->toBe('usuario@example.com');
});

it('renders the register screen', function () {
    Native::visit('/register')
        ->assertSee('Criar Conta')
        ->assertSee('Cadastre-se para conversar com seus contatos');
});

it('renders the conversations list on root route', function () {
    AuthSession::query()->create([
        'user_id' => 1,
        'name' => 'Teste',
        'email' => 'teste@example.com',
        'token' => 'token123',
        'is_active' => true,
    ]);

    $contact = Contact::firstOrCreate(['name' => 'Mariana Souza'], ['email' => 'mariana@example.com']);
    Conversation::firstOrCreate(['contact_id' => $contact->id], [
        'last_message_content' => 'Olá mundo!',
        'last_message_at' => now(),
        'unread_count' => 0,
    ]);

    Native::visit('/')
        ->assertSee('WhatsApp')
        ->assertSee('Mariana Souza');
});

it('renders the contacts list', function () {
    Contact::firstOrCreate(['name' => 'Beatriz Lima'], ['email' => 'beatriz@example.com']);

    Native::visit('/contacts')
        ->assertSee('Novo Chat')
        ->assertSee('Beatriz Lima');
});

it('renders the chat screen and sends a message', function () {
    $contact = Contact::firstOrCreate(['name' => 'Lucas Rocha'], ['email' => 'lucas@example.com']);
    $conversation = Conversation::firstOrCreate(['contact_id' => $contact->id], [
        'last_message_content' => 'Teste inicial',
        'last_message_at' => now(),
        'unread_count' => 0,
    ]);

    Native::visit('/chat/'.$conversation->id)
        ->assertSee('Lucas Rocha');

    Native::test(Chat::class, ['id' => $conversation->id])
        ->set('newMessage', 'Mensagem de teste automatizado')
        ->call('sendMessage')
        ->assertSet('newMessage', '');

    expect(Message::where('conversation_id', $conversation->id)->where('content', 'Mensagem de teste automatizado')->exists())->toBeTrue();
});
