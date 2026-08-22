<?php

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use App\NativeComponents\Chat;
use App\NativeComponents\Contacts;
use App\NativeComponents\Conversations;
use App\NativeComponents\Login;
use App\NativeComponents\Register;
use App\NativeComponents\Requests;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    Http::fake([
        '*/api/auth/login' => Http::response(['token' => 'fake_jwt_token', 'user' => ['id' => 1, 'name' => 'Usuário Teste', 'email' => 'usuario@example.com', 'username' => 'usuario_teste']], 200),
        '*/api/auth/register' => Http::response(['token' => 'fake_jwt_token', 'user' => ['id' => 1, 'name' => 'Usuário Teste', 'email' => 'usuario@example.com', 'username' => 'usuario_teste']], 201),
        '*/api/conversations' => Http::response([], 200),
        '*/api/conversations/requests' => Http::response(['totalPending' => 1, 'requests' => [
            [
                'id' => 15,
                'sender' => ['id' => 2, 'name' => 'Felipe Dev', 'username' => 'felipe_dev', 'avatarUrl' => null],
                'initialMessage' => ['id' => 89, 'content' => 'Olá! Vi seu projeto', 'status' => 'DELIVERED', 'createdAt' => '2026-08-22T18:00:00.000Z'],
            ],
        ]], 200),
        '*/api/conversations/*/accept' => Http::response(['success' => true, 'status' => 'ACCEPTED'], 200),
        '*/api/conversations/*/reject' => Http::response(['success' => true, 'status' => 'REJECTED'], 200),
        '*/api/users/search*' => Http::response([
            ['id' => 5, 'name' => 'Carlos Silva', 'username' => 'carlos_dev', 'avatarUrl' => null],
        ], 200),
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

it('registers a new user with @username', function () {
    Native::test(Register::class)
        ->set('name', 'Felipe Dev')
        ->set('username', '@felipe_dev')
        ->set('email', 'felipe@example.com')
        ->set('password', '123456')
        ->call('submit');

    expect(AuthSession::current())->not->toBeNull()
        ->and(AuthSession::current()?->username)->toBe('usuario_teste');
});

it('logs out and clears active session', function () {
    AuthSession::query()->create([
        'user_id' => 1,
        'name' => 'Teste',
        'email' => 'teste@example.com',
        'token' => 'token123',
        'is_active' => true,
    ]);

    Native::test(Conversations::class)
        ->call('logout');

    expect(AuthSession::current())->toBeNull();
});

it('renders the register screen with username field', function () {
    Native::visit('/register')
        ->assertSee('Criar Conta')
        ->assertSee('Cadastre-se com seu nome e @username');
});

it('renders the conversations list and shows message requests banner', function () {
    AuthSession::query()->create([
        'user_id' => 1,
        'name' => 'Teste',
        'email' => 'teste@example.com',
        'token' => 'token123',
        'is_active' => true,
    ]);

    $contact = Contact::firstOrCreate(['name' => 'Mariana Souza'], ['email' => 'mariana@example.com', 'username' => 'mariana_souza']);
    Conversation::firstOrCreate(['contact_id' => $contact->id], [
        'status' => 'ACCEPTED',
        'last_message_content' => 'Olá mundo!',
        'last_message_at' => now(),
        'unread_count' => 0,
    ]);

    Native::visit('/')
        ->assertSee('WhatsApp')
        ->assertSee('Mariana Souza');
});

it('renders the requests list screen', function () {
    Native::visit('/requests')
        ->assertSee('Solicitações de Mensagem');

    Native::test(Requests::class)
        ->call('refreshRequests');

    expect(Conversation::where('status', 'PENDING')->exists())->toBeTrue();
});

it('searches users by @username in contacts screen', function () {
    Native::visit('/contacts')
        ->assertSee('Novo Chat');

    Native::test(Contacts::class)
        ->set('searchQuery', 'carlos_dev')
        ->call('onSearchChange')
        ->assertSet('isSearching', true);
});

it('renders chat in request mode with action buttons and accepts request', function () {
    $contact = Contact::firstOrCreate(['name' => 'Novo Contato'], ['email' => 'novo@example.com', 'username' => 'novo_contato']);
    $conversation = Conversation::firstOrCreate(['contact_id' => $contact->id], [
        'remote_id' => 15,
        'status' => 'PENDING',
        'last_message_content' => 'Gostaria de conversar!',
        'last_message_at' => now(),
        'unread_count' => 1,
    ]);

    Native::visit('/chat/'.$conversation->id)
        ->assertSee('Novo Contato')
        ->assertSee('Aceitar')
        ->assertSee('Recusar')
        ->assertSee('Bloquear');

    Native::test(Chat::class, ['id' => $conversation->id])
        ->call('acceptRequest');

    expect($conversation->fresh()->status)->toBe('ACCEPTED');
});
