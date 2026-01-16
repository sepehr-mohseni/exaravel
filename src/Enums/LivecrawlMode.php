<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Enums;

/**
 * Livecrawl mode enumeration for Exa.ai API.
 */
enum LivecrawlMode: string
{
    case Always = 'always';
    case Fallback = 'fallback';
    case Never = 'never';

    /**
     * Get the default livecrawl mode.
     */
    public static function default(): self
    {
        return self::Fallback;
    }
}
