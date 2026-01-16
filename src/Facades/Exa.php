<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Facades;

use Illuminate\Support\Facades\Facade;
use Sepehr_Mohseni\Exaravel\Builders\AnswerBuilder;
use Sepehr_Mohseni\Exaravel\Builders\ContentsBuilder;
use Sepehr_Mohseni\Exaravel\Builders\FindSimilarBuilder;
use Sepehr_Mohseni\Exaravel\Builders\SearchBuilder;
use Sepehr_Mohseni\Exaravel\ExaClient;
use Sepehr_Mohseni\Exaravel\ExaManager;

/**
 * Facade for the Exa.ai client.
 *
 * @method static SearchBuilder search(string $query)
 * @method static FindSimilarBuilder findSimilar(string $url)
 * @method static ContentsBuilder contents(array<string> $ids)
 * @method static AnswerBuilder answer(string $query)
 * @method static ExaClient connection(?string $name = null)
 * @method static ExaClient using(string $connection)
 * @method static ExaClient withApiKey(string $apiKey)
 * @method static ExaClient driver(?string $driver = null)
 *
 * @see ExaManager
 */
final class Exa extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ExaManager::class;
    }
}
