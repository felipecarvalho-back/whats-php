@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior do Chat --}}
    <native:top-bar
        :title="$contact?->name ?? 'Chat'"
        :subtitle="$contact?->status_message ?? 'Online'"
    />

    {{-- Lista de Mensagens com Scroll Ancorado no Fim --}}
    <native:scroll-view scroll-anchor="bottom" class="w-full flex-1 bg-theme-surface-variant px-3 py-4">
        <native:column class="w-full gap-2">
            @forelse($messages as $message)
                <native:message-bubble
                    :message="$message"
                    key="msg-{{ $message->id }}"
                />
            @empty
                <native:column fill center class="py-12 gap-2">
                    <native:text class="text-sm text-theme-on-surface-variant text-center">
                        Nenhuma mensagem aqui ainda.
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant text-center opacity-75">
                        Envie um "Oi" para iniciar o papo!
                    </native:text>
                </native:column>
            @endforelse
        </native:column>
    </native:scroll-view>

    {{-- Barra de Digitação Fixada na Base com Ajuste de Teclado --}}
    <native:bottom-bar class="w-full bg-theme-surface px-3 py-2 border-t border-theme-outline">
        <native:row class="w-full items-center gap-2">
            <native:bare-text-input
                native:model="newMessage"
                placeholder="Mensagem"
                class="flex-1 px-4 py-2.5 rounded-full bg-theme-surface-variant text-theme-on-surface text-base"
                keep-focus-on-submit
            />

            <native:button
                variant="primary"
                a11y-label="Enviar mensagem"
                class="w-11 h-11 rounded-full items-center justify-center p-0"
                @press="sendMessage"
            >
                <native:icon :ios="Ios::PaperplaneFill" :android="Android::Send" :size="18" class="text-theme-on-primary" />
            </native:button>
        </native:row>
    </native:bottom-bar>
</native:column>
