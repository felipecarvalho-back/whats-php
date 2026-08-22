@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior do Chat com Botão de Voltar --}}
    <native:top-bar
        :title="$contact?->name ?? 'Chat'"
        :subtitle="$contact?->status_message ?? 'Online'"
    >
        <native:top-bar-action
            id="back"
            label="Voltar"
            :ios-icon="Ios::ChevronLeft"
            :android-icon="Android::ArrowBack"
            @press="goBack"
        />
    </native:top-bar>

    {{-- Lista de Mensagens com Scroll Ancorado no Fim --}}
    <native:scroll-view scroll-anchor="bottom" class="w-full flex-1 bg-theme-surface-variant px-2 py-3">
        <native:column class="w-full gap-2">
            {{-- Pílula Centralizada de Data (WhatsApp Style) --}}
            <native:row class="w-full justify-center my-1.5">
                <native:column class="px-3 py-1 rounded-lg bg-theme-surface/90 shadow-xs border border-theme-outline items-center justify-center">
                    <native:text class="text-xs font-medium text-theme-on-surface-variant">
                        Hoje
                    </native:text>
                </native:column>
            </native:row>

            {{-- Mensagens --}}
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
                        Envie uma mensagem para iniciar a conversa!
                    </native:text>
                </native:column>
            @endforelse
        </native:column>
    </native:scroll-view>

    {{-- Barra de Digitação Fixada na Base (Composer WhatsApp) --}}
    <native:bottom-bar class="w-full bg-theme-surface px-2 py-2 border-t border-theme-outline">
        <native:row class="w-full items-center gap-2">
            {{-- Cápsula do Campo de Texto com Ícones --}}
            <native:row class="flex-1 items-center bg-theme-surface-variant rounded-full px-3 py-0.5 gap-2">
                <native:icon :ios="Ios::FaceSmiling" :android="Android::SentimentSatisfiedAlt" :size="20" class="text-theme-on-surface-variant opacity-70" />
                
                <native:bare-text-input
                    native:model="newMessage"
                    placeholder="Mensagem"
                    class="flex-1 py-2 text-theme-on-surface text-base"
                    keep-focus-on-submit
                />
                
                <native:icon :ios="Ios::Paperclip" :android="Android::AttachFile" :size="20" class="text-theme-on-surface-variant opacity-70" />
            </native:row>

            {{-- Botão Redondo de Envio (WhatsApp Style) --}}
            <native:pressable
                @press="sendMessage"
                a11y-label="Enviar mensagem"
                class="w-11 h-11 rounded-full bg-theme-primary items-center justify-center"
            >
                <native:icon :ios="Ios::PaperplaneFill" :android="Android::Send" :size="18" class="text-theme-on-primary" />
            </native:pressable>
        </native:row>
    </native:bottom-bar>
</native:column>
