<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Exceptions;

/**
 * Exception thrown when the request is invalid (400).
 */
final class ValidationException extends ExaException
{
    /** @var array<string, mixed> */
    private array $errors;

    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message,
        array $errors = [],
        ?string $requestId = null,
    ) {
        $this->errors = $errors;
        parent::__construct($message, 400, $requestId);
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function withErrors(array $errors, ?string $requestId = null): self
    {
        $message = 'Invalid request parameters.';

        if (! empty($errors)) {
            $message .= ' Errors: '.json_encode($errors, JSON_THROW_ON_ERROR);
        }

        return new self(
            message: $message,
            errors: $errors,
            requestId: $requestId,
        );
    }
}
