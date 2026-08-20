@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:pressable
    @press="openChat"
    a11y-label="Conversa com {{ $contact?->name ?? 'Contato' }}"
    class="w-full px-4 py-3 bg-theme-surface border-b border-theme-outline items-center gap-3"
>
    <native:row class="w-full items-center gap-3">
        {{-- Avatar com Monograma / Iniciais --}}
        <native:column class="w-12 h-12 rounded-full bg-theme-secondary items-center justify-center">
            <native:text class="text-base font-bold text-theme-on-secondary">
                {{ $contact?->initials ?? 'U' }}
            </native:text>
        </native:column>

        {{-- Detalhes da Conversa --}}
        <native:column class="flex-1 gap-1">
            <native:row class="w-full justify-between items-center">
                <native:text class="text-base font-bold text-theme-on-surface">
                    {{ $contact?->name ?? 'Conversa' }}
                </native:text>

                @if($conversation?->last_message_at)
                    <native:text class="text-xs text-theme-on-surface-variant">
                        {{ $conversation->last_message_at->format('H:i') }}
                    </native:text>
                @endif
            </native:row>

            <native:row class="w-full justify-between items-center">
                <native:text class="text-sm text-theme-on-surface-variant flex-1" max-lines="1">
                    {{ $conversation?->last_message_content ?? 'Toque para iniciar a conversa' }}
                </native:text>

                @if(($conversation?->unread_count ?? 0) > 0)
                    <native:column class="px-2 py-0.5 rounded-full bg-theme-primary items-center justify-center min-w-[20]">
                        <native:text class="text-xs font-bold text-theme-on-primary">
                            {{ $conversation->unread_count }}
                        </native:text>
                    </native:column>
                @endif
            </native:row>
        </native:column>
    </native:row>
</native:pressable>
