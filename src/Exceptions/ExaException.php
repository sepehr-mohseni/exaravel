<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

use Exception;

/**
 * Base exception for all Exa.ai API exceptions.
 */
class ExaException extends Exception
{
    protected ?string $requestId;

    public function __construct(
        string $message,
        int $code = 0,
        ?string $requestId = null,
        ?\Throwable $previous = null,
    ) {
        $this->requestId = $requestId;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the request ID if available.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get a detailed error message including request ID if available.
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();

        if ($this->requestId !== null) {
            $message .= " [Request ID: {$this->requestId}]";
        }

        return $message;
    }
}
