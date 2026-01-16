<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Sepehr_Mohseni\Exaravel\ExaravelServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear singleton instances and cached drivers to pick up config changes
        $this->app->forgetInstance(\Sepehr_Mohseni\Exaravel\ExaManager::class);
        $this->app->forgetInstance('exa');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ExaravelServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Exa' => \Sepehr_Mohseni\Exaravel\Facades\Exa::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('exaravel.api_key', '****');
        $app['config']->set('exaravel.base_url', 'https://api.exa.ai');
        $app['config']->set('exaravel.timeout', 30);
        $app['config']->set('exaravel.logging.enabled', false);
    }
}
