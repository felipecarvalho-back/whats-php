<?php

namespace App\NativeComponents;

use App\Models\Contact;
use App\Models\Conversation;
use App\Services\ApiService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Edge\NativeComponent;

class Contacts extends NativeComponent
{
    public function mount(): void
    {
        $this->refreshContacts();
    }

    public function startChat(int $contactId): void
    {
        $contact = Contact::find($contactId);
        if (! $contact) {
            return;
        }

        $conversation = Conversation::firstOrCreate(
            ['contact_id' => $contact->id],
            [
                'last_message_content' => null,
                'last_message_at' => now(),
                'unread_count' => 0,
            ]
        );

        // Se o contato tiver remote_id e a conversa local não tiver remote_id, vincula via API
        if (! $conversation->remote_id && $contact->remote_id) {
            try {
                $response = app(ApiService::class)->createConversation($contact->remote_id);
                if (! empty($response['id'])) {
                    $conversation->update(['remote_id' => $response['id']]);
                }
            } catch (Exception $e) {
                // Mantém local
            }
        }

        $this->navigate('/chat/'.$conversation->id);
    }

    public function refreshContacts(): void
    {
        try {
            $apiService = app(ApiService::class);
            $remoteContacts = $apiService->getContacts();
            foreach ($remoteContacts as $remote) {
                Contact::query()->updateOrCreate(
                    ['remote_id' => $remote['id']],
                    [
                        'name' => $remote['name'],
                        'email' => $remote['email'] ?? null,
                        'avatar_url' => $remote['avatarUrl'] ?? null,
                        'status_message' => 'Disponível',
                    ]
                );
            }
        } catch (Exception $e) {
            // Mantém os contatos locais
        }
    }

    /**
     * @return Collection<int, Contact>
     */
    #[Computed]
    public function contacts(): Collection
    {
        return Contact::query()->orderBy('name', 'asc')->get();
    }

    public function render(): View
    {
        return view('native.contacts', [
            'contacts' => $this->contacts,
        ]);
    }
}
