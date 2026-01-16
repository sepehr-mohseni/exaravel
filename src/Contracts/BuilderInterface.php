<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Contracts;

/**
 * Contract for request builders.
 */
interface BuilderInterface
{
    /**
     * Build the request payload.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;
}
