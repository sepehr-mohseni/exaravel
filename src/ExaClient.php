<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sepehr_Mohseni\Exaravel\Builders\AnswerBuilder;
use Sepehr_Mohseni\Exaravel\Builders\ContentsBuilder;
use Sepehr_Mohseni\Exaravel\Builders\FindSimilarBuilder;
use Sepehr_Mohseni\Exaravel\Builders\SearchBuilder;
use Sepehr_Mohseni\Exaravel\Contracts\ExaClientInterface;
use Sepehr_Mohseni\Exaravel\DTOs\AnswerResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ContentsResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ExaResponse;
use Sepehr_Mohseni\Exaravel\Exceptions\AuthenticationException;
use Sepehr_Mohseni\Exaravel\Exceptions\ExaHandler;

/**
 * Main client for interacting with Exa.ai API.
 */
final class ExaClient implements ExaClientInterface
{
    private string $baseUrl;

    private int $timeout;

    private int $connectTimeout;

    /** @var array{times: int, sleep_milliseconds: int, when: array<int>} */
    private array $retryConfig;

    /** @var array{enabled: bool, channel: string} */
    private array $loggingConfig;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $apiKey,
        array $config = [],
    ) {
        $this->baseUrl = $config['base_url'] ?? config('exaravel.base_url', 'https://api.exa.ai');
        $this->timeout = $config['timeout'] ?? config('exaravel.timeout', 30);
        $this->connectTimeout = $config['connect_timeout'] ?? config('exaravel.connect_timeout', 10);
        $this->retryConfig = $config['retry'] ?? config('exaravel.retry', [
            'times' => 3,
            'sleep_milliseconds' => 500,
            'when' => [429, 500, 502, 503, 504],
        ]);
        $this->loggingConfig = $config['logging'] ?? config('exaravel.logging', [
            'enabled' => false,
            'channel' => 'stack',
        ]);
    }

    /**
     * Create a new search builder.
     */
    public function search(string $query): SearchBuilder
    {
        return new SearchBuilder($this, $query);
    }

    /**
     * Create a new find similar builder.
     */
    public function findSimilar(string $url): FindSimilarBuilder
    {
        return new FindSimilarBuilder($this, $url);
    }

    /**
     * Create a new contents builder.
     *
     * @param  array<string>  $ids
     */
    public function contents(array $ids): ContentsBuilder
    {
        return new ContentsBuilder($this, $ids);
    }

    /**
     * Create a new answer builder.
     */
    public function answer(string $query): AnswerBuilder
    {
        return new AnswerBuilder($this, $query);
    }

    /**
     * Execute a search request.
     *
     * @internal
     */
    public function executeSearch(SearchBuilder $builder): ExaResponse
    {
        $response = $this->sendRequest('POST', '/search', $builder->toPayload());

        return ExaResponse::fromArray(
            $response->json(),
            $response->header('x-request-id')
        );
    }

    /**
     * Execute a find similar request.
     *
     * @internal
     */
    public function executeFindSimilar(FindSimilarBuilder $builder): ExaResponse
    {
        $response = $this->sendRequest('POST', '/findSimilar', $builder->toPayload());

        return ExaResponse::fromArray(
            $response->json(),
            $response->header('x-request-id')
        );
    }

    /**
     * Execute a contents request.
     *
     * @internal
     */
    public function executeContents(ContentsBuilder $builder): ContentsResponse
    {
        $response = $this->sendRequest('POST', '/contents', $builder->toPayload());

        return ContentsResponse::fromArray(
            $response->json(),
            $response->header('x-request-id')
        );
    }

    /**
     * Execute an answer request.
     *
     * @internal
     */
    public function executeAnswer(AnswerBuilder $builder): AnswerResponse
    {
        $response = $this->sendRequest('POST', '/answer', $builder->toPayload());

        return AnswerResponse::fromArray(
            $response->json(),
            $response->header('x-request-id')
        );
    }

    /**
     * Create a configured pending request.
     */
    private function createPendingRequest(): PendingRequest
    {
        if (empty($this->apiKey)) {
            throw AuthenticationException::missingApiKey();
        }

        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->retry(
                times: $this->retryConfig['times'],
                sleepMilliseconds: $this->retryConfig['sleep_milliseconds'],
                when: fn (
                    \Exception $exception,
                    PendingRequest $request
                ): bool => $exception instanceof \Illuminate\Http\Client\RequestException
                    && ExaHandler::isRetryable($exception->response->status()),
                throw: false,
            );

        return $request;
    }

    /**
     * Send an API request.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendRequest(string $method, string $endpoint, array $payload): Response
    {
        $requestId = $this->generateRequestId();

        // Add request ID to Laravel Context for observability (Laravel 11+)
        if (class_exists(\Illuminate\Support\Facades\Context::class)) {
            \Illuminate\Support\Facades\Context::add('exa_request_id', $requestId);
        }

        $this->logRequest($endpoint, $payload, $requestId);

        $request = $this->createPendingRequest();

        $startTime = microtime(true);

        /** @var Response $response */
        $response = $method === 'GET'
            ? $request->get($endpoint, $payload)
            : $request->post($endpoint, $payload);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->logResponse($endpoint, $response, $requestId, $duration);

        // Handle errors through the exception handler
        ExaHandler::handle($response);

        return $response;
    }

    /**
     * Generate a unique request ID for tracking.
     */
    private function generateRequestId(): string
    {
        return sprintf(
            'exa_%s_%s',
            date('YmdHis'),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * Log outgoing request if logging is enabled.
     *
     * @param  array<string, mixed>  $payload
     */
    private function logRequest(string $endpoint, array $payload, string $requestId): void
    {
        if (! $this->loggingConfig['enabled']) {
            return;
        }

        Log::channel($this->loggingConfig['channel'])->debug('Exa.ai API Request', [
            'request_id' => $requestId,
            'endpoint' => $endpoint,
            'payload' => $payload,
        ]);
    }

    /**
     * Log incoming response if logging is enabled.
     */
    private function logResponse(
        string $endpoint,
        Response $response,
        string $requestId,
        float $duration
    ): void {
        if (! $this->loggingConfig['enabled']) {
            return;
        }

        $context = [
            'request_id' => $requestId,
            'api_request_id' => $response->header('x-request-id'),
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'duration_ms' => round($duration, 2),
        ];

        if (! $response->successful()) {
            $context['error'] = $response->json();
            Log::channel($this->loggingConfig['channel'])->error('Exa.ai API Error', $context);
        } else {
            Log::channel($this->loggingConfig['channel'])->debug('Exa.ai API Response', $context);
        }
    }

    /**
     * Get the configured API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
