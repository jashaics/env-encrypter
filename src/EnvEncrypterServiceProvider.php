<?php

namespace Jviscuso\EnvEncrypter;

use Illuminate\Support\ServiceProvider;
use Jviscuso\EnvEncrypter\Console\Commands\Encrypt;
use Jviscuso\EnvEncrypter\Console\Commands\Decrypt;



class EnvEncrypterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(\Illuminate\Routing\Router $router)
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
    public function register()
    {
        // Automatically apply the package configuration
        // $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'env-encrypter');
        parent::register();
    }
}
