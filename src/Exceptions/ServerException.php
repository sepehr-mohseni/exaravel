<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

/**
 * Exception thrown when a server error occurs (5xx).
 */
final class ServerException extends ExaException
{
    public static function create(int $statusCode, ?string $message = null, ?string $requestId = null): self
    {
        return new self(
            message: $message ?? "Exa.ai server error occurred (HTTP {$statusCode}).",
            code: $statusCode,
            requestId: $requestId,
        );
    }

    public static function unavailable(?string $requestId = null): self
    {
        return new self(
            message: 'Exa.ai service is temporarily unavailable. Please try again later.',
            code: 503,
            requestId: $requestId,
        );
    }
}
