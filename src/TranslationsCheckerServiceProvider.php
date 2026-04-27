<?php

namespace SertxuDeveloper\TranslationsChecker;

use Illuminate\Support\ServiceProvider;
use SertxuDeveloper\TranslationsChecker\Services\TranslationCheckerService;

final class TranslationsCheckerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(TranslationCheckerService::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\CheckTranslations::class,
            ]);
        }
    }
}
