<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

/**
 * Exception thrown when the API rate limit is exceeded (429).
 */
final class RateLimitException extends ExaException
{
    private ?int $retryAfter;

    public function __construct(
        string $message,
        ?int $retryAfter = null,
        ?string $requestId = null,
    ) {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, 429, $requestId);
    }

    /**
     * Get the retry-after value in seconds.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public static function exceeded(?int $retryAfter = null, ?string $requestId = null): self
    {
        $message = 'API rate limit exceeded.';

        if ($retryAfter !== null) {
            $message .= " Retry after {$retryAfter} seconds.";
        }

        return new self(
            message: $message,
            retryAfter: $retryAfter,
            requestId: $requestId,
        );
    }
}
