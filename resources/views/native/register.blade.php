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
                <native:icon :ios="Ios::PersonBadgePlus" :android="Android::PersonAdd" :size="32" class="text-theme-on-primary" />
            </native:column>
            
            <native:text class="text-2xl font-bold text-theme-on-surface text-center">
                Criar Conta
            </native:text>
            
            <native:text class="text-sm text-theme-on-surface-variant text-center">
                Cadastre-se para conversar com seus contatos
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
                native:model="name"
                label="Nome completo"
                placeholder="Seu nome"
                class="w-full"
            />

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
                placeholder="Crie uma senha segura"
                secure
                class="w-full"
            />
        </native:column>

        {{-- Botão de Cadastro --}}
        <native:button
            label="{{ $loading ? 'Cadastrando...' : 'Cadastrar' }}"
            variant="primary"
            class="w-full mt-2"
            :disabled="$loading"
            @press="submit"
        />

        <native:divider class="w-full my-2 border-theme-outline" />

        {{-- Link para Login --}}
        <native:row class="w-full justify-center items-center gap-1">
            <native:text class="text-sm text-theme-on-surface-variant">
                Já possui uma conta?
            </native:text>
            <native:pressable @press="goToLogin" a11y-label="Ir para tela de login">
                <native:text class="text-sm font-bold text-theme-primary">
                    Entrar
                </native:text>
            </native:pressable>
        </native:row>
    </native:column>
</native:column>
