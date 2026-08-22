@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:scroll-view class="w-full flex-1 bg-theme-background">
    <native:column
        fill
        center
        class="safe-area px-5 py-6 min-h-full justify-center"
    >
        <native:column class="w-full max-w-[400] rounded-2xl bg-theme-surface p-6 shadow-sm gap-4">
            {{-- Cabeçalho / Logo --}}
            <native:column class="w-full items-center gap-1.5 mb-1">
                <native:column class="w-14 h-14 rounded-full bg-theme-primary items-center justify-center mb-1">
                    <native:icon :ios="Ios::MessageFill" :android="Android::Chat" :size="28" class="text-theme-on-primary" />
                </native:column>
                
                <native:text class="text-2xl font-bold text-theme-on-surface text-center">
                    WhatsApp Native
                </native:text>
                
                <native:text class="text-xs text-theme-on-surface-variant text-center">
                    Digite suas credenciais para entrar
                </native:text>
            </native:column>

            {{-- Mensagem de Informação / Erro --}}
            @if(!empty($errorMessage))
                <native:column class="w-full p-3 rounded-xl bg-theme-destructive/15 border border-theme-destructive">
                    <native:text class="text-xs text-theme-destructive font-medium text-center leading-relaxed">
                        {{ $errorMessage }}
                    </native:text>
                </native:column>
            @endif

            {{-- Campos de Formulário Modernos --}}
            <native:column class="w-full gap-3">
                {{-- E-mail ou @username --}}
                <native:column class="w-full gap-1">
                    <native:text class="text-xs font-semibold text-theme-on-surface-variant">
                        E-mail ou @username
                    </native:text>
                    <native:row class="w-full bg-theme-surface-variant rounded-xl px-3.5 py-1.5 items-center border border-theme-outline">
                        <native:bare-text-input
                            native:model="email"
                            placeholder="seu.email@exemplo.com ou @usuario"
                            class="w-full text-sm text-theme-on-surface"
                        />
                    </native:row>
                </native:column>

                {{-- Senha --}}
                <native:column class="w-full gap-1">
                    <native:text class="text-xs font-semibold text-theme-on-surface-variant">
                        Senha
                    </native:text>
                    <native:row class="w-full bg-theme-surface-variant rounded-xl px-3.5 py-1.5 items-center border border-theme-outline">
                        <native:bare-text-input
                            native:model="password"
                            placeholder="Digite sua senha"
                            secure
                            class="w-full text-sm text-theme-on-surface"
                        />
                    </native:row>
                </native:column>
            </native:column>

            {{-- Botão de Entrar --}}
            <native:button
                label="{{ !empty($loading) ? 'Entrando...' : 'Entrar' }}"
                variant="primary"
                class="w-full mt-1"
                :disabled="!empty($loading)"
                @press="submit"
            />

            {{-- Painel de Configuração de IP do Servidor --}}
            @if(!empty($showServerConfig))
                <native:column class="w-full p-3 rounded-xl bg-theme-surface-variant border border-theme-outline gap-2 mt-1">
                    <native:text class="text-xs font-bold text-theme-on-surface">
                        Configurar Servidor NestJS
                    </native:text>

                    {{-- Atalhos de 1 Toque --}}
                    <native:row class="w-full gap-1.5 flex-wrap">
                        <native:pressable
                            @press="setPresetUrl('http://127.0.0.1:3000/api')"
                            a11y-label="Conectar via cabo USB em 127.0.0.1"
                            class="px-2 py-1 rounded-md bg-theme-surface border border-theme-outline items-center justify-center"
                        >
                            <native:text class="text-[11px] font-medium text-theme-on-surface">
                                🔌 USB: 127.0.0.1
                            </native:text>
                        </native:pressable>

                        <native:pressable
                            @press="setPresetUrl('http://192.168.1.11:3000/api')"
                            a11y-label="Conectar via Wi-Fi em 192.168.1.11"
                            class="px-2 py-1 rounded-md bg-theme-surface border border-theme-outline items-center justify-center"
                        >
                            <native:text class="text-[11px] font-medium text-theme-on-surface">
                                📶 Wi-Fi: 192.168.1.11
                            </native:text>
                        </native:pressable>

                        <native:pressable
                            @press="setPresetUrl('http://10.0.2.2:3000/api')"
                            a11y-label="Conectar via Emulador em 10.0.2.2"
                            class="px-2 py-1 rounded-md bg-theme-surface border border-theme-outline items-center justify-center"
                        >
                            <native:text class="text-[11px] font-medium text-theme-on-surface">
                                📱 Emulador: 10.0.2.2
                            </native:text>
                        </native:pressable>
                    </native:row>
                    
                    <native:row class="w-full bg-theme-surface rounded-xl px-3 py-1 items-center border border-theme-outline mt-1">
                        <native:bare-text-input
                            native:model="serverUrl"
                            placeholder="http://127.0.0.1:3000/api"
                            class="w-full text-xs text-theme-on-surface"
                        />
                    </native:row>

                    <native:row class="w-full justify-end gap-2 mt-1">
                        <native:pressable
                            @press="toggleServerConfig"
                            a11y-label="Cancelar configuração de servidor"
                            class="px-3 py-1.5 rounded-lg border border-theme-outline items-center justify-center"
                        >
                            <native:text class="text-xs font-medium text-theme-on-surface-variant">
                                Fechar
                            </native:text>
                        </native:pressable>

                        <native:pressable
                            @press="saveServerConfig"
                            a11y-label="Salvar IP do servidor"
                            class="px-3 py-1.5 rounded-lg bg-theme-primary items-center justify-center"
                        >
                            <native:text class="text-xs font-bold text-theme-on-primary">
                                Salvar URL
                            </native:text>
                        </native:pressable>
                    </native:row>
                </native:column>
            @else
                <native:row class="w-full justify-center items-center mt-0.5">
                    <native:pressable
                        @press="toggleServerConfig"
                        a11y-label="Configurar IP do servidor"
                        class="py-1 px-2 items-center"
                    >
                        <native:text class="text-xs text-theme-on-surface-variant text-center opacity-80">
                            ⚙️ Servidor: {{ $serverUrl ?? 'http://127.0.0.1:3000/api' }}
                        </native:text>
                    </native:pressable>
                </native:row>
            @endif

            <native:divider class="w-full my-0.5 border-theme-outline" />

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
</native:scroll-view>
