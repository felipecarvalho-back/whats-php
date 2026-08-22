@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior Nativa com Botão de Voltar --}}
    <native:top-bar
        title="Novo Chat"
        subtitle="{{ $isSearching ? count($searchResults) . ' resultado(s)' : $contacts->count() . ' contatos' }}"
    >
        <native:top-bar-action
            id="back"
            label="Voltar"
            :ios-icon="Ios::ChevronLeft"
            :android-icon="Android::ArrowBack"
            @press="goBack"
        />
    </native:top-bar>

    {{-- Campo de Busca por @username ou Nome --}}
    <native:column class="w-full px-4 py-2.5 bg-theme-surface border-b border-theme-outline gap-2">
        <native:row class="w-full items-center bg-theme-surface-variant rounded-xl px-3 py-1 gap-2">
            <native:icon :ios="Ios::Magnifyingglass" :android="Android::Search" :size="20" class="text-theme-on-surface-variant opacity-70" />
            
            <native:bare-text-input
                native:model="searchQuery"
                placeholder="Buscar por @username ou nome..."
                class="flex-1 py-1.5 text-theme-on-surface text-sm"
                @submit="onSearchChange"
            />

            @if($searchQuery)
                <native:pressable @press="clearSearch" a11y-label="Limpar busca" class="p-1">
                    <native:icon :ios="Ios::XmarkCircleFill" :android="Android::Cancel" :size="18" class="text-theme-on-surface-variant opacity-70" />
                </native:pressable>
            @endif
        </native:row>

        @if($searchQuery && ! $isSearching)
            <native:pressable
                @press="onSearchChange"
                a11y-label="Buscar usuários na nuvem"
                class="w-full py-1.5 px-3 bg-theme-primary/10 rounded-lg items-center justify-center"
            >
                <native:text class="text-xs font-bold text-theme-primary">
                    🔍 Buscar "{{ $searchQuery }}" no servidor NestJS
                </native:text>
            </native:pressable>
        @endif
    </native:column>

    {{-- Resultados da Busca (Nuvem NestJS) --}}
    @if($isSearching)
        <native:column class="w-full flex-1">
            <native:row class="w-full px-4 py-2 bg-theme-surface-variant/40 border-b border-theme-outline justify-between items-center">
                <native:text class="text-xs font-bold text-theme-on-surface-variant">
                    RESULTADOS DA BUSCA
                </native:text>
                <native:pressable @press="clearSearch" a11y-label="Ver todos os contatos">
                    <native:text class="text-xs font-medium text-theme-primary">
                        Ver contatos locais
                    </native:text>
                </native:pressable>
            </native:row>

            <native:list class="w-full flex-1">
                @forelse($searchResults as $user)
                    <native:pressable
                        @press="startChatWithUser({{ $user['id'] }}, '{{ $user['name'] }}', '{{ $user['username'] ?? '' }}', '{{ $user['avatarUrl'] ?? '' }}')"
                        a11y-label="Conversar com {{ $user['name'] }}"
                        key="search-user-{{ $user['id'] }}"
                        class="w-full px-4 py-3 bg-theme-surface border-b border-theme-outline items-center gap-3"
                    >
                        <native:row class="w-full items-center gap-3">
                            <native:column class="w-11 h-11 rounded-full bg-theme-primary items-center justify-center">
                                <native:text class="text-sm font-bold text-theme-on-primary">
                                    {{ mb_strtoupper(mb_substr($user['name'], 0, 1)) }}
                                </native:text>
                            </native:column>

                            <native:column class="flex-1 gap-0.5">
                                <native:text class="text-base font-bold text-theme-on-surface">
                                    {{ $user['name'] }}
                                </native:text>
                                
                                @if(!empty($user['username']))
                                    <native:text class="text-xs font-medium text-theme-primary">
                                        {{ '@' . $user['username'] }}
                                    </native:text>
                                @endif
                            </native:column>

                            <native:column class="px-3 py-1.5 rounded-lg bg-theme-primary/10 border border-theme-primary/20">
                                <native:text class="text-xs font-bold text-theme-primary">
                                    Conversar
                                </native:text>
                            </native:column>
                        </native:row>
                    </native:pressable>
                @empty
                    <native:column fill center class="py-12 gap-2">
                        <native:text class="text-base font-medium text-theme-on-surface text-center">
                            Nenhum usuário encontrado para "{{ $searchQuery }}"
                        </native:text>
                        <native:text class="text-xs text-theme-on-surface-variant text-center">
                            Tente buscar pelo @username exato cadastrado.
                        </native:text>
                    </native:column>
                @endforelse
            </native:list>
        </native:column>
    @else
        {{-- Lista de Contatos Locais --}}
        <native:list on-refresh="refreshContacts" class="w-full flex-1">
            @forelse($contacts as $contact)
                <native:pressable
                    @press="startChat({{ $contact->id }})"
                    a11y-label="Conversar com {{ $contact->name }}"
                    key="contact-{{ $contact->id }}"
                    class="w-full px-4 py-3 bg-theme-surface border-b border-theme-outline items-center gap-3"
                >
                    <native:row class="w-full items-center gap-3">
                        {{-- Avatar com Iniciais --}}
                        <native:column class="w-11 h-11 rounded-full bg-theme-secondary items-center justify-center">
                            <native:text class="text-sm font-bold text-theme-on-secondary">
                                {{ $contact->initials }}
                            </native:text>
                        </native:column>

                        {{-- Nome, @username e Status do Contato --}}
                        <native:column class="flex-1 gap-0.5">
                            <native:text class="text-base font-bold text-theme-on-surface">
                                {{ $contact->name }}
                            </native:text>

                            @if($contact->username)
                                <native:text class="text-xs font-medium text-theme-primary">
                                    {{ '@' . $contact->username }}
                                </native:text>
                            @endif

                            <native:text class="text-sm text-theme-on-surface-variant">
                                {{ $contact->status_message ?? 'Disponível' }}
                            </native:text>
                        </native:column>
                    </native:row>
                </native:pressable>
            @empty
                <native:column fill center class="py-12 px-6 gap-2">
                    <native:text class="text-base font-bold text-theme-on-surface text-center">
                        Nenhum contato encontrado
                    </native:text>
                    <native:text class="text-sm text-theme-on-surface-variant text-center">
                        Use o campo de busca acima para encontrar usuários pelo @username.
                    </native:text>
                </native:column>
            @endforelse
        </native:list>
    @endif
</native:column>
