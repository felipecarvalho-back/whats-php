<?php

use App\Providers\AppServiceProvider;
use App\Providers\NativeServiceProvider;
use Blutrixx\Fcm\FcmServiceProvider;
use Unloc\NativephpEnhancedSplash\NativephpEnhancedSplashServiceProvider;

return [
    AppServiceProvider::class,
    NativeServiceProvider::class,
    NativephpEnhancedSplashServiceProvider::class,
    FcmServiceProvider::class,
];
