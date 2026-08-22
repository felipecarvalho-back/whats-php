@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior do Chat com Botão de Voltar --}}
    <native:top-bar
        :title="$contact?->name ?? 'Chat'"
        :subtitle="$contact?->username ? '@' . $contact->username : ($contact?->status_message ?? 'Online')"
    >
        <native:top-bar-action
            id="back"
            label="Voltar"
            :ios-icon="Ios::ChevronLeft"
            :android-icon="Android::ArrowBack"
            @press="goBack"
        />
    </native:top-bar>

    {{-- Feedback de Ação (ex: Solicitação aceita) --}}
    @if($actionFeedback)
        <native:column class="w-full px-4 py-2 bg-theme-primary/15 border-b border-theme-primary items-center">
            <native:text class="text-xs font-bold text-theme-primary text-center">
                {{ $actionFeedback }}
            </native:text>
        </native:column>
    @endif

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

            @if($conversation?->isPending())
                <native:row class="w-full justify-center my-1">
                    <native:column class="px-4 py-2 rounded-xl bg-theme-surface border border-theme-outline items-center justify-center max-w-[340]">
                        <native:text class="text-xs text-theme-on-surface-variant text-center leading-relaxed">
                            📬 Esta é uma solicitação de conversa. Responda aceitando para liberar o chat direto.
                        </native:text>
                    </native:column>
                </native:row>
            @endif

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

    {{-- Barra Inferior (Composer Normal OU Rodapé de Decisão Instagram Direct) --}}
    <native:bottom-bar>
        @if($conversation?->isPending())
            {{-- Rodapé de Solicitação (Instagram Direct: Bloquear / Recusar / Aceitar) --}}
            <native:column class="w-full bg-theme-surface px-4 pt-3 pb-8 border-t border-theme-outline gap-3">
                <native:text class="text-xs text-theme-on-surface-variant text-center leading-relaxed">
                    @if($contact?->username)
                        {{ '@' . $contact->username }} quer enviar uma mensagem para você.
                    @else
                        {{ $contact?->name ?? 'Este usuário' }} quer enviar uma mensagem para você.
                    @endif
                    Aceite para liberar o envio mútuo de mensagens.
                </native:text>

                <native:row class="w-full justify-between gap-2">
                    <native:pressable
                        @press="blockContact"
                        a11y-label="Bloquear este usuário"
                        class="flex-1 py-2.5 rounded-xl border border-theme-destructive items-center justify-center bg-theme-destructive/10"
                    >
                        <native:text class="text-xs font-bold text-theme-destructive">
                            🚫 Bloquear
                        </native:text>
                    </native:pressable>

                    <native:pressable
                        @press="rejectRequest"
                        a11y-label="Recusar esta solicitação"
                        class="flex-1 py-2.5 rounded-xl border border-theme-outline items-center justify-center bg-theme-surface-variant"
                    >
                        <native:text class="text-xs font-bold text-theme-on-surface-variant">
                            🗑️ Recusar
                        </native:text>
                    </native:pressable>

                    <native:pressable
                        @press="acceptRequest"
                        a11y-label="Aceitar solicitação de conversa"
                        class="flex-1 py-2.5 rounded-xl bg-theme-primary items-center justify-center shadow-xs"
                    >
                        <native:text class="text-xs font-bold text-theme-on-primary">
                            🟢 Aceitar
                        </native:text>
                    </native:pressable>
                </native:row>
            </native:column>
        @else
            {{-- Barra de Digitação Fixada na Base Elevada (Composer WhatsApp) --}}
            <native:column class="w-full bg-theme-surface px-3 pt-2.5 pb-8 border-t border-theme-outline">
                <native:row class="w-full items-center gap-2">
                    {{-- Cápsula do Campo de Texto com Ícones --}}
                    <native:row class="flex-1 items-center bg-theme-surface-variant rounded-full px-4 py-1 gap-2.5">
                        <native:icon :ios="Ios::FaceSmiling" :android="Android::SentimentSatisfiedAlt" :size="22" class="text-theme-on-surface-variant opacity-70" />
                        
                        <native:bare-text-input
                            native:model="newMessage"
                            placeholder="Mensagem"
                            class="flex-1 py-2 text-theme-on-surface text-base"
                            keep-focus-on-submit
                        />
                        
                        <native:icon :ios="Ios::Paperclip" :android="Android::AttachFile" :size="22" class="text-theme-on-surface-variant opacity-70" />
                    </native:row>

                    {{-- Botão Redondo de Envio Elevado (WhatsApp Style) --}}
                    <native:pressable
                        @press="sendMessage"
                        a11y-label="Enviar mensagem"
                        class="w-12 h-12 rounded-full bg-theme-primary items-center justify-center shadow-xs"
                    >
                        <native:icon :ios="Ios::PaperplaneFill" :android="Android::Send" :size="20" class="text-theme-on-primary" />
                    </native:pressable>
                </native:row>
            </native:column>
        @endif
    </native:bottom-bar>
</native:column>
