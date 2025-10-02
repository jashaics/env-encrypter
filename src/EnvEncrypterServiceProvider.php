<?php

namespace Jashaics\EnvEncrypter;

use Illuminate\Support\ServiceProvider;
use Jashaics\EnvEncrypter\Console\Commands\Decrypt;
use Jashaics\EnvEncrypter\Console\Commands\Encrypt;

class EnvEncrypterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        /*
         * Optional methods to load your package assets
         */
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'env-encrypter');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Encrypt::class,
                Decrypt::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        parent::register();
    }
}
