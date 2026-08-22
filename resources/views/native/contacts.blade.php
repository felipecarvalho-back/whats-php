@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior Nativa com Botão de Voltar --}}
    <native:top-bar
        title="Novo Chat"
        subtitle="{{ $isSearching ? (count($searchResults) . ' encontrado(s) na nuvem') : ($contacts->count() . ' contatos') }}"
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
        <native:row class="w-full items-center bg-theme-surface-variant rounded-xl px-3 py-1 gap-2 border border-theme-outline">
            <native:icon :ios="Ios::Magnifyingglass" :android="Android::Search" :size="20" class="text-theme-on-surface-variant opacity-70" />
            
            <native:bare-text-input
                native:model="searchQuery"
                @change="onSearchChange"
                @submit="onSearchChange"
                placeholder="Buscar @username ou nome..."
                class="flex-1 py-1.5 text-theme-on-surface text-sm"
            />

            @if($searchQuery)
                <native:pressable @press="clearSearch" a11y-label="Limpar busca" class="p-1">
                    <native:icon :ios="Ios::XmarkCircleFill" :android="Android::Cancel" :size="18" class="text-theme-on-surface-variant opacity-70" />
                </native:pressable>
            @endif

            {{-- Botão de Busca --}}
            <native:pressable
                @press="onSearchChange"
                a11y-label="Executar busca de usuários"
                class="px-2.5 py-1 rounded-lg bg-theme-primary items-center justify-center shadow-xs"
            >
                <native:text class="text-xs font-bold text-theme-on-primary">
                    Buscar
                </native:text>
            </native:pressable>
        </native:row>
    </native:column>

    {{-- Resultados da Busca --}}
    @if($isSearching)
        <native:list class="w-full flex-1">
            {{-- Seção: Usuários Encontrados na Nuvem NestJS --}}
            <native:column class="w-full px-4 py-2 bg-theme-surface-variant/40 border-b border-theme-outline">
                <native:text class="text-xs font-bold text-theme-on-surface-variant">
                    USUÁRIOS ENCONTRADOS NA NUVEM ({{ count($searchResults) }})
                </native:text>
            </native:column>

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
                <native:column class="w-full py-8 px-6 items-center justify-center gap-1.5 bg-theme-surface border-b border-theme-outline">
                    <native:text class="text-sm font-medium text-theme-on-surface text-center">
                        Nenhum usuário novo encontrado na nuvem para "{{ $searchQuery }}"
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant text-center opacity-75">
                        Tente o @username cadastrado (ex: @felp, @teste).
                    </native:text>
                </native:column>
            @endforelse

            {{-- Seção: Contatos Locais Filtrados --}}
            @if($contacts->isNotEmpty())
                <native:column class="w-full px-4 py-2 bg-theme-surface-variant/40 border-b border-theme-outline mt-2">
                    <native:text class="text-xs font-bold text-theme-on-surface-variant">
                        CONTATOS SALVOS CORRESPONDENTES ({{ $contacts->count() }})
                    </native:text>
                </native:column>

                @foreach($contacts as $contact)
                    <native:pressable
                        @press="startChat({{ $contact->id }})"
                        a11y-label="Conversar com {{ $contact->name }}"
                        key="contact-search-{{ $contact->id }}"
                        class="w-full px-4 py-3 bg-theme-surface border-b border-theme-outline items-center gap-3"
                    >
                        <native:row class="w-full items-center gap-3">
                            <native:column class="w-11 h-11 rounded-full bg-theme-secondary items-center justify-center">
                                <native:text class="text-sm font-bold text-theme-on-secondary">
                                    {{ $contact->initials }}
                                </native:text>
                            </native:column>

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
                @endforeach
            @endif
        </native:list>
    @else
        {{-- Lista de Contatos Locais Padrão --}}
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
                        Digite um @username na barra acima para buscar na nuvem.
                    </native:text>
                </native:column>
            @endforelse
        </native:list>
    @endif
</native:column>
