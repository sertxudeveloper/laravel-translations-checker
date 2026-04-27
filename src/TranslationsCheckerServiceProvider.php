<?php

namespace SertxuDeveloper\TranslationsChecker;

use Illuminate\Support\ServiceProvider;

final class TranslationsCheckerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
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
