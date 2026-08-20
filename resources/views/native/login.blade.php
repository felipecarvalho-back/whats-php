@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column
    fill
    center
    class="safe-area bg-theme-background px-6 py-8"
>
    <native:column class="w-full max-w-[400] rounded-2xl bg-theme-surface p-8 shadow-sm gap-4">
        {{-- Cabeçalho / Logo --}}
        <native:column class="w-full items-center gap-2 mb-4">
            <native:column class="w-16 h-16 rounded-full bg-theme-primary items-center justify-center mb-2">
                <native:icon :ios="Ios::MessageFill" :android="Android::Chat" :size="32" class="text-theme-on-primary" />
            </native:column>
            
            <native:text class="text-2xl font-bold text-theme-on-surface text-center">
                WhatsApp Native
            </native:text>
            
            <native:text class="text-sm text-theme-on-surface-variant text-center">
                Digite suas credenciais para entrar
            </native:text>
        </native:column>

        {{-- Mensagem de Erro --}}
        @if($errorMessage)
            <native:column class="w-full p-3 rounded-lg bg-theme-destructive/15 border border-theme-destructive">
                <native:text class="text-sm text-theme-destructive font-medium text-center">
                    {{ $errorMessage }}
                </native:text>
            </native:column>
        @endif

        {{-- Campos de Formulário --}}
        <native:column class="w-full gap-3">
            <native:outlined-text-input
                native:model="email"
                label="E-mail"
                placeholder="seu.email@exemplo.com"
                keyboard="email"
                class="w-full"
            />

            <native:outlined-text-input
                native:model="password"
                label="Senha"
                placeholder="Digite sua senha"
                secure
                class="w-full"
            />
        </native:column>

        {{-- Botão de Entrar --}}
        <native:button
            label="{{ $loading ? 'Entrando...' : 'Entrar' }}"
            variant="primary"
            class="w-full mt-2"
            :disabled="$loading"
            @press="submit"
        />

        <native:divider class="w-full my-2 border-theme-outline" />

        {{-- Link para Cadastro --}}
        <native:row class="w-full justify-center items-center gap-1">
            <native:text class="text-sm text-theme-on-surface-variant">
                Não tem uma conta?
            </native:text>
            <native:pressable @press="goToRegister" a11y-label="Cadastre-se para criar uma conta">
                <native:text class="text-sm font-bold text-theme-primary">
                    Cadastre-se
                </native:text>
            </native:pressable>
        </native:row>
    </native:column>
</native:column>
