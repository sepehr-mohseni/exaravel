<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Enums;

/**
 * Search type enumeration for Exa.ai API.
 */
enum SearchType: string
{
    case Auto = 'auto';
    case Neural = 'neural';
    case Keyword = 'keyword';

    /**
     * Get the default search type.
     */
    public static function default(): self
    {
        return self::Auto;
    }

    /**
     * Create from string value with fallback.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::default();
    }
}
