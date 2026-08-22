@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior Nativa --}}
    <native:top-bar title="WhatsApp">
        <native:top-bar-action
            id="logout"
            label="Sair"
            :ios-icon="Ios::RectanglePortraitAndArrowRight"
            :android-icon="Android::Logout"
            @press="logout"
        />
        <native:top-bar-action
            id="new_chat"
            label="Novo Chat"
            :ios-icon="Ios::SquareAndPencil"
            :android-icon="Android::Chat"
            @press="newChat"
        />
    </native:top-bar>

    {{-- Banner de Solicitações de Mensagem Estilo Instagram Direct --}}
    @if($pendingRequestsCount > 0)
        <native:pressable
            @press="goToRequests"
            a11y-label="Ver {{ $pendingRequestsCount }} solicitações de mensagem pendentes"
            class="w-full px-4 py-3 bg-theme-primary/10 border-b border-theme-primary/20 items-center justify-between"
        >
            <native:row class="w-full items-center justify-between">
                <native:row class="items-center gap-2.5">
                    <native:column class="w-8 h-8 rounded-full bg-theme-primary items-center justify-center">
                        <native:icon :ios="Ios::TrayFill" :android="Android::Inbox" :size="16" class="text-theme-on-primary" />
                    </native:column>
                    
                    <native:column class="gap-0.5">
                        <native:text class="text-sm font-bold text-theme-on-surface">
                            Solicitações de Mensagem
                        </native:text>
                        <native:text class="text-xs text-theme-primary font-medium">
                            {{ $pendingRequestsCount }} pendente(s) • Toque para ver
                        </native:text>
                    </native:column>
                </native:row>

                <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="18" class="text-theme-on-surface-variant" />
            </native:row>
        </native:pressable>
    @endif

    {{-- Lista de Conversas --}}
    @if($conversations->isNotEmpty())
        <native:list on-refresh="refreshConversations" class="w-full flex-1">
            @foreach($conversations as $conversation)
                <native:conversation-card
                    :conversation="$conversation"
                    key="conv-{{ $conversation->id }}"
                />
            @endforeach
        </native:list>
    @else
        <native:column fill center class="p-8 gap-4">
            <native:column class="w-20 h-20 rounded-full bg-theme-surface-variant items-center justify-center">
                <native:icon :ios="Ios::Message" :android="Android::ChatBubbleOutline" :size="36" class="text-theme-on-surface-variant" />
            </native:column>

            <native:text class="text-lg font-bold text-theme-on-surface text-center">
                Nenhuma conversa ainda
            </native:text>

            <native:text class="text-sm text-theme-on-surface-variant text-center">
                Toque no botão abaixo para iniciar uma nova conversa com um contato ou @username.
            </native:text>

            <native:button
                label="Iniciar Conversa"
                variant="primary"
                @press="newChat"
            />
        </native:column>
    @endif

    {{-- Botão Flutuante (FAB) --}}
    <native:fab
        a11y-label="Iniciar nova conversa"
        :ios-icon="Ios::SquareAndPencil"
        :android-icon="Android::Chat"
        @press="newChat"
    />
</native:column>
