<?php

return [

    /*
    |--------------------------------------------------------------------------
    | iOS Splash Configuration
    |--------------------------------------------------------------------------
    */
    'ios' => [
        'mode' => env('ENHANCED_SPLASH_IOS_MODE', 'icon'),
        'background' => env('ENHANCED_SPLASH_IOS_BACKGROUND', '#FFFFFF'),
        'background_dark' => env('ENHANCED_SPLASH_IOS_BACKGROUND_DARK', '#111B21'),
        'icon_size' => env('ENHANCED_SPLASH_IOS_ICON_SIZE', 160),
        'icon_rounded' => env('ENHANCED_SPLASH_IOS_ICON_ROUNDED', true),
        'icon_shadow' => env('ENHANCED_SPLASH_IOS_ICON_SHADOW', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Android Splash Configuration
    |--------------------------------------------------------------------------
    */
    'android' => [
        'mode' => env('ENHANCED_SPLASH_ANDROID_MODE', 'icon'),
        'background' => env('ENHANCED_SPLASH_ANDROID_BACKGROUND', '#FFFFFF'),
        'background_dark' => env('ENHANCED_SPLASH_ANDROID_BACKGROUND_DARK', '#111B21'),
    ],

];
