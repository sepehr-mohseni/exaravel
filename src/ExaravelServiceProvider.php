<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Sepehr_Mohseni\Exaravel\Contracts\ExaClientInterface;

/**
 * Service provider for the Exaravel package.
 */
final class ExaravelServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/exaravel.php',
            'exaravel'
        );

        $this->registerManager();
        $this->registerClient();
    }

    /**
     * Register the Exa manager.
     */
    protected function registerManager(): void
    {
        $this->app->singleton(ExaManager::class, function (Application $app): ExaManager {
            return new ExaManager($app);
        });

        $this->app->alias(ExaManager::class, 'exa');
    }

    /**
     * Register the Exa client interface.
     */
    protected function registerClient(): void
    {
        $this->app->bind(ExaClientInterface::class, function (Application $app): ExaClient {
            /** @var ExaManager $manager */
            $manager = $app->make(ExaManager::class);

            return $manager->driver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/exaravel.php' => config_path('exaravel.php'),
            ], 'exaravel-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ExaManager::class,
            ExaClientInterface::class,
            'exa',
        ];
    }
}
