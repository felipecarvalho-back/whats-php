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
    public function startChat(int $contactId): void
    {
        $conversation = Conversation::firstOrCreate(
            ['contact_id' => $contactId],
            [
                'last_message_content' => null,
                'last_message_at' => now(),
                'unread_count' => 0,
            ]
        );

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
