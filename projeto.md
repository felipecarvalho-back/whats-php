# Proposta de Arquitetura e Roadmap: Chat Mobile em Tempo Real

**Stack Tecnológica:** NativePHP Mobile (v4) + NestJS (TypeScript / Socket.io)  
**Objetivo:** Construção de um protótipo funcional de aplicativo de mensagens instantâneas com foco na experiência do usuário móvel e sincronização em tempo real de baixa latência.

---

## 1. Visão Geral da Arquitetura

```
┌──────────────────────────────────────────────────────────┐
│              CLIENTE: NativePHP Mobile                   │
│  - Interface: Native Components + Blade / Tailwind       │
│  - Armazenamento Local: SQLite (Offline-First)          │
│  - Comunicação: HTTP Client (REST) + Socket.io Client    │
└────────────────────────────┬─────────────────────────────┘
                             │
                  REST HTTP  │  WebSockets (WS)
                             ▼
┌──────────────────────────────────────────────────────────┐
│              BACKEND: NestJS API Gateway                │
│  - Autenticação: JWT Simples                             │
│  - Tempo Real: @WebSocketGateway() (Socket.io)           │
│  - Banco de Dados: SQLite / Prisma ORM                   │
│  - Notificações em Segundo Plano: Firebase Admin (FCM)   │
└──────────────────────────────────────────────────────────┘
```

---

## 2. Roadmap de Desenvolvimento

### Fase 1: Core Mobile (NativePHP) — Prioridade Inicial
*Objetivo: Criar a interface nativa, navegação fluida e armazenamento local de dados.*

* **1.1. Autenticação e Entrada:**
  * Telas de login e cadastro simplificadas via `NativeComponent`.
  * Persistência de token de sessão e identificador do usuário logado.
* **1.2. Lista de Conversas:**
  * Visualização de contatos, avatar, prévia da última mensagem e indicador de mensagens não lidas.
  * Ação rápida para iniciar nova conversa por identificador de usuário.
* **1.3. Interface de Chat (Bate-papo):**
  * Lista com balões de mensagem estilizados (diferenciação visual de emissor e receptor).
  * Barra inferior fixa com campo de texto e botão nativo de envio.
  * Auto-scroll automático para a mensagem mais recente ao carregar ou receber novos itens.
* **1.4. Persistência Local (SQLite):**
  * Estrutura de banco local no SQLite para renderização instantânea do histórico sem loading de rede ao abrir o app.

---

### Fase 2: Sincronização em Tempo Real (Integração NestJS ↔ NativePHP)
*Objetivo: Estabelecer a troca bidirecional de mensagens entre os dispositivos.*

* **2.1. Gateway de WebSockets (NestJS):**
  * Criação de salas privadas por ID de conversa (`chat:room_{id}`).
  * Eventos principais: `joinRoom`, `sendMessage`, `newMessage` e `typingStatus` (digitando...).
* **2.2. Conexão Socket no Mobile:**
  * Escuta de eventos Socket.io dentro das views do NativePHP.
  * Atualização dinâmica da lista de mensagens sem recarregar a tela.
* **2.3. Status de Entrega:**
  * Indicadores visuais de status da mensagem (enviando, entregue, lida).

---

### Fase 3: Recursos Nativos e Segundo Plano
*Objetivo: Integrar recursos de hardware e entrega de notificações.*

* **3.1. Notificações Push (FCM):**
  * Coleta e envio do token do dispositivo via plugin de push no NativePHP (`fatlum/nativephp-push`).
  * Disparo de push notifications pelo NestJS quando o destinatário estiver com o aplicativo fechado ou minimizado.
* **3.2. Envio de Imagens/Fotos:**
  * Integração com a câmera nativa (`nativephp/mobile-camera`) para envio de fotos diretamente no chat.
* **3.3. Feedback Tátil e Sonoro:**
  * Emissão de sons nativos e vibrações sutis ao receber ou enviar mensagens.

---

## 3. Matriz de Entregáveis

| Etapa | Módulo | Entregável Principal |
| :--- | :--- | :--- |
| **Etapa 1** | Mobile UI | Telas de Chat, Lista de Conversas e Login com Native Components. |
| **Etapa 2** | API NestJS | Servidor com rotas HTTP básicas + Socket.io Gateway funcional. |
| **Etapa 3** | Integração | Envio e recebimento bidirecional de mensagens entre 2 celulares. |
| **Etapa 4** | Background | Push Notifications via Firebase para mensagens com o app fechado. |

---

## 4. Pontos Técnicos para Avaliação

1. **Separação de Responsabilidades:** O backend NestJS atua apenas como coordenador de mensagens e autenticação, reduzindo a complexidade de infraestrutura.
2. **Performance e Bateria:** WebSockets ativos apenas com o app em primeiro plano; notificações em segundo plano delegadas ao FCM.
3. **Experiência Offline-First:** O histórico salvo no SQLite local garante abertura instantânea do app, eliminando telas de loading desnecessárias.