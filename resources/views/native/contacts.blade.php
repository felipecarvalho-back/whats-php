@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    {{-- Barra Superior Nativa --}}
    <native:top-bar
        title="Novo Chat"
        subtitle="{{ $contacts->count() }} contatos"
    />

    {{-- Lista de Contatos --}}
    <native:list on-refresh="refreshContacts" class="w-full flex-1">
        @foreach($contacts as $contact)
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

                    {{-- Nome e Status do Contato --}}
                    <native:column class="flex-1 gap-0.5">
                        <native:text class="text-base font-bold text-theme-on-surface">
                            {{ $contact->name }}
                        </native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">
                            {{ $contact->status_message ?? 'Disponível' }}
                        </native:text>
                    </native:column>
                </native:row>
            </native:pressable>
        @endforeach
    </native:list>
</native:column>
