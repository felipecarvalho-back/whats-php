@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior Nativa com Botão de Voltar --}}
    <native:top-bar
        title="Solicitações de Mensagem"
        subtitle="{{ $pendingRequests->count() }} pendente(s)"
    >
        <native:top-bar-action
            id="back"
            label="Voltar"
            :ios-icon="Ios::ChevronLeft"
            :android-icon="Android::ArrowBack"
            @press="goBack"
        />
    </native:top-bar>

    {{-- Explicação de Privacidade estilo Instagram --}}
    <native:column class="w-full px-4 py-3 bg-theme-surface-variant/50 border-b border-theme-outline">
        <native:text class="text-xs text-theme-on-surface-variant leading-relaxed">
            Abra uma solicitação para ler a mensagem. A pessoa não saberá que você visualizou até você aceitar.
        </native:text>
    </native:column>

    {{-- Lista de Solicitações --}}
    <native:list on-refresh="refreshRequests" class="w-full flex-1">
        @forelse($pendingRequests as $request)
            <native:pressable
                @press="openRequest({{ $request->id }})"
                a11y-label="Abrir solicitação de {{ $request->contact?->name ?? 'Usuário' }}"
                key="req-{{ $request->id }}"
                class="w-full px-4 py-3.5 bg-theme-surface border-b border-theme-outline gap-3"
            >
                <native:row class="w-full items-center gap-3">
                    {{-- Avatar com Monograma --}}
                    <native:column class="w-12 h-12 rounded-full bg-theme-primary/20 items-center justify-center">
                        <native:text class="text-base font-bold text-theme-primary">
                            {{ $request->contact?->initials ?? 'U' }}
                        </native:text>
                    </native:column>

                    {{-- Nome, @username e Trecho da Mensagem --}}
                    <native:column class="flex-1 gap-0.5">
                        <native:row class="w-full justify-between items-center">
                            <native:text class="text-base font-bold text-theme-on-surface">
                                {{ $request->contact?->name ?? 'Usuário' }}
                            </native:text>

                            @if($request->last_message_at)
                                <native:text class="text-xs text-theme-on-surface-variant opacity-75">
                                    {{ $request->last_message_at->format('H:i') }}
                                </native:text>
                            @endif
                        </native:row>

                        @if($request->contact?->username)
                            <native:text class="text-xs font-medium text-theme-primary">
                                {{ '@' . $request->contact->username }}
                            </native:text>
                        @endif

                        <native:text class="text-sm text-theme-on-surface-variant" max-lines="1">
                            {{ $request->last_message_content ?? 'Enviou uma solicitação de mensagem' }}
                        </native:text>
                    </native:column>
                </native:row>
            </native:pressable>
        @empty
            <native:column fill center class="py-16 px-6 gap-3">
                <native:column class="w-16 h-16 rounded-full bg-theme-surface-variant items-center justify-center mb-1">
                    <native:icon :ios="Ios::Tray" :android="Android::Inbox" :size="32" class="text-theme-on-surface-variant opacity-70" />
                </native:column>

                <native:text class="text-base font-bold text-theme-on-surface text-center">
                    Nenhuma solicitação no momento
                </native:text>

                <native:text class="text-sm text-theme-on-surface-variant text-center leading-relaxed">
                    Quando pessoas que não são seus contatos enviarem uma mensagem pelo seu @username, ela aparecerá aqui.
                </native:text>
            </native:column>
        @endforelse
    </native:list>
</native:column>
