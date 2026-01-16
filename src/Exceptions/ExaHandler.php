<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * Maps HTTP responses to domain-specific exceptions.
 */
final readonly class ExaHandler
{
    /**
     * Handle an HTTP response and throw appropriate exception if needed.
     *
     * @throws ExaException
     */
    public static function handle(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $requestId = $response->header('x-request-id');
        $body = $response->json() ?? [];
        $message = $body['message'] ?? $body['error'] ?? null;
        $retryAfterHeader = $response->header('retry-after');

        throw match ($response->status()) {
            400 => ValidationException::withErrors($body['errors'] ?? [], $requestId),
            401 => AuthenticationException::invalidApiKey($requestId),
            402 => InsufficientCreditsException::create($requestId),
            404 => NotFoundException::create($message, $requestId),
            429 => RateLimitException::exceeded(
                retryAfter: $retryAfterHeader !== '' ? (int) $retryAfterHeader : null,
                requestId: $requestId,
            ),
            500, 502, 503, 504 => ServerException::create($response->status(), $message, $requestId),
            default => new ExaException(
                message: $message ?? "Unexpected API error (HTTP {$response->status()})",
                code: $response->status(),
                requestId: $requestId,
            ),
        };
    }

    /**
     * Check if a status code is retryable.
     */
    public static function isRetryable(int $statusCode): bool
    {
        return in_array($statusCode, [429, 500, 502, 503, 504], true);
    }
}
