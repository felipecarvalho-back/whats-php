<?php

use App\Providers\AppServiceProvider;
use App\Providers\NativeServiceProvider;
use Unloc\NativephpEnhancedSplash\NativephpEnhancedSplashServiceProvider;

return [
    AppServiceProvider::class,
    NativeServiceProvider::class,
    NativephpEnhancedSplashServiceProvider::class,
];
