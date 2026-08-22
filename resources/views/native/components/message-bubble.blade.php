@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:row
    class="w-full mb-1.5 px-2 {{ $isOutgoing ? 'justify-end' : 'justify-start' }}"
>
    <native:column
        class="max-w-[340] px-3.5 py-2 rounded-2xl shadow-xs gap-0.5 {{ $isOutgoing ? 'bg-theme-chat-outgoing rounded-tr-xs' : 'bg-theme-chat-incoming rounded-tl-xs' }}"
    >
        {{-- Conteúdo do texto da mensagem --}}
        <native:text class="text-base font-normal leading-normal {{ $isOutgoing ? 'text-theme-on-chat-outgoing' : 'text-theme-on-chat-incoming' }}">
            {{ $message?->content ?? '' }}
        </native:text>

        {{-- Metadados: Horário e Status (ajuste dinâmico ao conteúdo) --}}
        <native:row class="justify-end items-center gap-1 self-end">
            <native:text class="text-[11px] text-theme-on-surface-variant opacity-75">
                {{ $message?->created_at ? $message->created_at->format('H:i') : '' }}
            </native:text>

            @if($isOutgoing)
                @if($message?->status === 'pending')
                    <native:icon :ios="Ios::Clock" :android="Android::Schedule" :size="12" class="text-theme-on-surface-variant opacity-60" />
                @elseif($message?->status === 'read')
                    <native:icon :ios="Ios::CheckmarkCircleFill" :android="Android::DoneAll" :size="13" class="text-[#34B7F1]" />
                @elseif($message?->status === 'delivered')
                    <native:icon :ios="Ios::CheckmarkCircle" :android="Android::DoneAll" :size="13" class="text-theme-on-surface-variant opacity-75" />
                @else
                    <native:icon :ios="Ios::Checkmark" :android="Android::Check" :size="12" class="text-theme-on-surface-variant opacity-75" />
                @endif
            @endif
        </native:row>
    </native:column>
</native:row>
