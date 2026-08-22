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
    public string $searchQuery = '';

    public array $searchResults = [];

    public bool $isSearching = false;

    public bool $isSearchingLoading = false;

    public function mount(): void
    {
        $this->refreshContacts();
    }

    public function updatedSearchQuery(): void
    {
        $this->onSearchChange($this->searchQuery);
    }

    public function onSearchChange(?string $value = null): void
    {
        if ($value !== null) {
            $this->searchQuery = $value;
        }

        $query = trim($this->searchQuery);
        if (empty($query)) {
            $this->searchResults = [];
            $this->isSearching = false;
            $this->isSearchingLoading = false;

            return;
        }

        $this->isSearching = true;
        $this->isSearchingLoading = true;

        try {
            $apiService = app(ApiService::class);
            $cleanQuery = ltrim($query, '@');

            $results = $apiService->searchUsers($cleanQuery);

            // Fallback para username exato
            if (empty($results)) {
                $exactUser = $apiService->getUserByUsername($cleanQuery);
                if ($exactUser && ! empty($exactUser['id'])) {
                    $results = [$exactUser];
                }
            }

            $this->searchResults = $results;
        } catch (Exception $e) {
            $this->searchResults = [];
        } finally {
            $this->isSearchingLoading = false;
        }
    }

    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->isSearching = false;
        $this->isSearchingLoading = false;
    }

    public function startChatWithUser(int $remoteUserId, string $name, ?string $username = null, ?string $avatarUrl = null): void
    {
        $contact = Contact::query()->updateOrCreate(
            ['remote_id' => $remoteUserId],
            [
                'name' => $name,
                'username' => $username ? ltrim($username, '@') : null,
                'avatar_url' => $avatarUrl,
                'status_message' => 'Disponível',
            ]
        );

        $conversation = Conversation::firstOrCreate(
            ['contact_id' => $contact->id],
            [
                'last_message_content' => null,
                'last_message_at' => now(),
                'unread_count' => 0,
                'status' => 'ACCEPTED',
            ]
        );

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
                'status' => 'ACCEPTED',
            ]
        );

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
                        'username' => $remote['username'] ?? null,
                        'avatar_url' => $remote['avatarUrl'] ?? null,
                        'status_message' => 'Disponível',
                    ]
                );
            }
        } catch (Exception $e) {
            // Mantém os contatos locais
        }
    }

    public function goBack(): void
    {
        $this->replace('/');
    }

    /**
     * @return Collection<int, Contact>
     */
    #[Computed]
    public function contacts(): Collection
    {
        $query = Contact::query()->orderBy('name', 'asc');

        if (! empty(trim($this->searchQuery))) {
            $term = '%'.trim($this->searchQuery).'%';
            $cleanTerm = '%'.ltrim(trim($this->searchQuery), '@').'%';
            $query->where(function ($q) use ($term, $cleanTerm) {
                $q->where('name', 'like', $term)
                    ->orWhere('username', 'like', $cleanTerm)
                    ->orWhere('email', 'like', $term);
            });
        }

        return $query->get();
    }

    public function render(): View
    {
        return view('native.contacts', [
            'contacts' => $this->contacts,
            'searchQuery' => $this->searchQuery,
            'searchResults' => $this->searchResults,
            'isSearching' => $this->isSearching,
            'isSearchingLoading' => $this->isSearchingLoading,
        ]);
    }
}
