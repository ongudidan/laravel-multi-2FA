<?php

namespace Ongudidan\Multi2fa;

use Ongudidan\Multi2fa\Contracts\SettingsResolverInterface;
use Ongudidan\Multi2fa\Services\DefaultSettingsResolver;
use Ongudidan\Multi2fa\Services\TwoFactorService;
use Illuminate\Support\ServiceProvider;

class Multi2faServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/multi-2fa.php',
            'multi-2fa'
        );

        $this->app->singleton(SettingsResolverInterface::class, function ($app) {
            $resolverClass = config('multi-2fa.settings_resolver', DefaultSettingsResolver::class);
            return $app->make($resolverClass);
        });

        $this->app->singleton(TwoFactorService::class, function ($app) {
            return new TwoFactorService(
                $app->make(SettingsResolverInterface::class)
            );
        });

        // Convenient alias
        $this->app->alias(TwoFactorService::class, 'multi-2fa');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'multi-2fa');
        $this->registerPublishing();
        $this->registerRoutes();
    }

    /**
     * Register package publishable assets.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/multi-2fa.php' => config_path('multi-2fa.php'),
            ], 'multi-2fa-config');

            // Publish Vue / Inertia templates
            $this->publishes([
                __DIR__ . '/../stubs/vue/auth' => resource_path('js/pages/auth'),
                __DIR__ . '/../stubs/vue/settings' => resource_path('js/pages/settings'),
            ], 'multi-2fa-vue');

            // Publish Blade templates
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/multi-2fa'),
            ], 'multi-2fa-blade');
        }
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        if (config('multi-2fa.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}
