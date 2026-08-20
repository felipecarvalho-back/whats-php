<?php

/**
 * Native UI — Theme Tokens
 *
 * Published via `php artisan vendor:publish --tag=native-ui-config`.
 * Edit to customize your app's visual identity in one place.
 *
 * For dynamic per-tenant theming, use Nativephp\NativeUi\Theme::merge([...])
 * from a service provider. Runtime merges deep-merge on top of these values.
 *
 * Decision log: /docs/NATIVE-UI-REWRITE-PLAN.md (D — theme layer)
 */

return [

    /*
    |---------------------------------------------------------------------------
    | Theme
    |---------------------------------------------------------------------------
    |
    | 17 color tokens, 4 radii, 4 font sizes, font family.
    |
    | "on-X" means "color of content placed ON a surface of color X"
    |   — i.e., text/icons on that background.
    |
    | Color tokens accept:
    |   - CSS hex: '#B91C1C', '#F00', or with alpha '#8B5CF680' (#RRGGBBAA)
    |   - Tailwind palette names: 'red-300', 'orange-800'
    |   - Opacity modifiers on either: 'red-300/20', '#8B5CF6/50'
    |
    | Dark mode is auto-derived from `light` when `dark` is not set. To opt
    | into explicit dark tokens, fill out the `dark` block.
    |
    | The default pairs meet WCAG AA (4.5:1) — if you customize, keep each
    | `on-*` color at 4.5:1 contrast against its background token.
    |
    */

    'theme' => [

        'light' => [
            // WhatsApp Primary Green
            'primary' => '#00A884',
            'on-primary' => '#FFFFFF',

            // WhatsApp Teal / Dark Header
            'secondary' => '#128C7E',
            'on-secondary' => '#FFFFFF',

            // Surface = cards, sheets, dialogs. Background = page root.
            'surface' => '#FFFFFF',
            'on-surface' => '#111B21',
            'background' => '#F0F2F5',
            'on-background' => '#111B21',

            // Surface variant = chat background wallpaper tone.
            'surface-variant' => '#EFEAE2',
            'on-surface-variant' => '#667781',

            // Chat bubble tokens
            'chat-outgoing' => '#D9FDD3',
            'on-chat-outgoing' => '#111B21',
            'chat-incoming' => '#FFFFFF',
            'on-chat-incoming' => '#111B21',

            // Outline = neutral borders.
            'outline' => '#E9EDEF',

            // Destructive actions.
            'destructive' => '#EA0038',
            'on-destructive' => '#FFFFFF',

            // Tertiary accent.
            'accent' => '#25D366',
            'on-accent' => '#FFFFFF',
        ],

        'dark' => [
            'primary' => '#00A884',
            'on-primary' => '#FFFFFF',

            'secondary' => '#005C4B',
            'on-secondary' => '#E9EDEF',

            'surface' => '#111B21',
            'on-surface' => '#E9EDEF',
            'background' => '#0B141A',
            'on-background' => '#E9EDEF',

            'surface-variant' => '#121B22',
            'on-surface-variant' => '#8696A0',

            // Chat bubble tokens
            'chat-outgoing' => '#005C4B',
            'on-chat-outgoing' => '#E9EDEF',
            'chat-incoming' => '#202C33',
            'on-chat-incoming' => '#E9EDEF',

            'outline' => '#222D34',

            'destructive' => '#F15C6D',
            'on-destructive' => '#0B141A',

            'accent' => '#25D366',
            'on-accent' => '#0B141A',
        ],

        // Corner radii (points / dp).
        'radius-sm' => 4,
        'radius-md' => 8,
        'radius-lg' => 16,
        'radius-full' => 9999,

        // Font size scale (points / sp).
        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,
    ],

    'fonts' => [
        'default' => 'System',
        'accent' => 'Archivo+Black-Regular',
        'lobster' => 'Lobster+Two-Regular',
    ],

];
