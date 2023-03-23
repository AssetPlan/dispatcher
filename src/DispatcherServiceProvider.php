<?php

namespace Assetplan\Dispatcher;

use Assetplan\Dispatcher\Http\Middleware\DispatcherMiddleware;
use Illuminate\Support\ServiceProvider;

class DispatcherServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if (config('dispatcher.is_backend')) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }


        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('dispatcher.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'dispatcher');

        $this->app->singleton('dispatcher', Dispatcher::class);

        if (config('dispatcher.is_backend')) {
            $this->app['router']->aliasMiddleware('dispatcher-middleware', DispatcherMiddleware::class);
        }
    }
}
