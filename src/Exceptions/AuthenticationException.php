<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

/**
 * Exception thrown when authentication fails (401).
 */
final class AuthenticationException extends ExaException
{
    public static function invalidApiKey(?string $requestId = null): self
    {
        return new self(
            message: 'Invalid API key provided. Please check your EXA_API_KEY configuration.',
            code: 401,
            requestId: $requestId,
        );
    }

    public static function missingApiKey(): self
    {
        return new self(
            message: 'No API key configured. Please set EXA_API_KEY in your environment.',
            code: 401,
        );
    }
}
