<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

/**
 * Exception thrown when the account has insufficient credits (402).
 */
final class InsufficientCreditsException extends ExaException
{
    public static function create(?string $requestId = null): self
    {
        return new self(
            message: 'Insufficient API credits. Please add more credits to your Exa.ai account.',
            code: 402,
            requestId: $requestId,
        );
    }
}
