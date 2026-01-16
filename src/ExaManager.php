<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel;

use Illuminate\Support\Manager;
use Sepehr_Mohseni\Exaravel\Builders\AnswerBuilder;
use Sepehr_Mohseni\Exaravel\Builders\ContentsBuilder;
use Sepehr_Mohseni\Exaravel\Builders\FindSimilarBuilder;
use Sepehr_Mohseni\Exaravel\Builders\SearchBuilder;
use Sepehr_Mohseni\Exaravel\Contracts\ExaClientInterface;
use Sepehr_Mohseni\Exaravel\Exceptions\AuthenticationException;

/**
 * Manager for handling multiple Exa.ai client configurations.
 */
final class ExaManager extends Manager implements ExaClientInterface
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('exaravel.default', 'default');
    }

    /**
     * Create the default driver instance.
     */
    protected function createDefaultDriver(): ExaClient
    {
        return $this->createConnection('default');
    }

    /**
     * Create a driver instance.
     * Override to support dynamic connection names from config.
     */
    protected function createDriver(mixed $driver): ExaClient
    {
        // Check if there's a custom creator for this driver
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        // Check if there's a createXxxDriver method
        $method = 'create'.ucfirst($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        // For any named connection, create using configuration
        return $this->createConnection($driver);
    }

    /**
     * Create a new connection instance.
     */
    public function createConnection(string $name): ExaClient
    {
        $config = $this->getConnectionConfig($name);

        return new ExaClient(
            apiKey: $config['api_key'] ?? '',
            config: $config,
        );
    }

    /**
     * Get the configuration for a connection.
     *
     * @return array<string, mixed>
     */
    protected function getConnectionConfig(string $name): array
    {
        $connections = $this->config->get('exaravel.connections', []);

        if (isset($connections[$name])) {
            // Filter out null values from connection config to prevent overwriting defaults
            $connectionConfig = array_filter(
                $connections[$name],
                fn ($value) => $value !== null
            );

            return array_merge(
                $this->getDefaultConfig(),
                $connectionConfig
            );
        }

        // Fall back to default configuration
        return $this->getDefaultConfig();
    }

    /**
     * Get the default configuration values.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultConfig(): array
    {
        return [
            'api_key' => $this->config->get('exaravel.api_key'),
            'base_url' => $this->config->get('exaravel.base_url', 'https://api.exa.ai'),
            'timeout' => $this->config->get('exaravel.timeout', 30),
            'connect_timeout' => $this->config->get('exaravel.connect_timeout', 10),
            'retry' => $this->config->get('exaravel.retry', [
                'times' => 3,
                'sleep_milliseconds' => 500,
                'when' => [429, 500, 502, 503, 504],
            ]),
            'logging' => $this->config->get('exaravel.logging', [
                'enabled' => false,
                'channel' => 'stack',
            ]),
        ];
    }

    /**
     * Forget all cached driver instances.
     * Useful for testing when configuration changes.
     *
     * @return $this
     */
    public function forgetDrivers(): static
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(mixed $method, mixed $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }

    /**
     * Get a connection instance by name.
     */
    public function connection(?string $name = null): ExaClient
    {
        $name ??= $this->getDefaultDriver();

        return $this->driver($name);
    }

    /**
     * Start building a search request.
     */
    public function search(string $query): SearchBuilder
    {
        return $this->driver()->search($query);
    }

    /**
     * Start building a find similar request.
     */
    public function findSimilar(string $url): FindSimilarBuilder
    {
        return $this->driver()->findSimilar($url);
    }

    /**
     * Start building a contents request.
     *
     * @param  array<string>  $ids
     */
    public function contents(array $ids): ContentsBuilder
    {
        return $this->driver()->contents($ids);
    }

    /**
     * Start building an answer request.
     */
    public function answer(string $query): AnswerBuilder
    {
        return $this->driver()->answer($query);
    }

    /**
     * Use a specific connection.
     */
    public function using(string $connection): ExaClient
    {
        return $this->connection($connection);
    }

    /**
     * Create a new client instance with a custom API key.
     */
    public function withApiKey(string $apiKey): ExaClient
    {
        if (empty($apiKey)) {
            throw AuthenticationException::missingApiKey();
        }

        return new ExaClient(
            apiKey: $apiKey,
            config: $this->getDefaultConfig(),
        );
    }
}
