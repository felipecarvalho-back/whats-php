<?php

namespace App\Providers;

use App\NativeComponents\ConversationCard;
use App\NativeComponents\MessageBubble;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Native\Mobile\Edge\ComponentRegistry;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Auto-recriação de tabelas do SQLite caso o usuário limpe o armazenamento/cache do app no Android
        try {
            if (! app()->runningUnitTests() && (! Schema::hasTable('auth_sessions') || ! Schema::hasTable('conversations'))) {
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (Throwable $e) {
            // Silencia para manter inicialização fluida
        }

        ComponentRegistry::components([
            'conversation-card' => ConversationCard::class,
            'message-bubble' => MessageBubble::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
