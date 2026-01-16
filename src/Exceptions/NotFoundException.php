<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

/**
 * Exception thrown when a resource is not found (404).
 */
final class NotFoundException extends ExaException
{
    public static function create(?string $message = null, ?string $requestId = null): self
    {
        return new self(
            message: $message ?? 'The requested resource was not found.',
            code: 404,
            requestId: $requestId,
        );
    }
}
