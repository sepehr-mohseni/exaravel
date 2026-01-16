<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Enums;

/**
 * Content category enumeration for Exa.ai API.
 */
enum Category: string
{
    case Company = 'company';
    case ResearchPaper = 'research paper';
    case News = 'news';
    case GitHub = 'github';
    case Tweet = 'tweet';
    case Movie = 'movie';
    case Song = 'song';
    case PersonalSite = 'personal site';
    case PDF = 'pdf';
    case FinancialReport = 'financial report';

    /**
     * Get all available categories.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
