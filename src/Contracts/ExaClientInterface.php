<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Contracts;

use Sepehr_Mohseni\Exaravel\Builders\AnswerBuilder;
use Sepehr_Mohseni\Exaravel\Builders\ContentsBuilder;
use Sepehr_Mohseni\Exaravel\Builders\FindSimilarBuilder;
use Sepehr_Mohseni\Exaravel\Builders\SearchBuilder;

/**
 * Contract for Exa.ai client implementations.
 */
interface ExaClientInterface
{
    /**
     * Start building a search request.
     */
    public function search(string $query): SearchBuilder;

    /**
     * Start building a find similar request.
     */
    public function findSimilar(string $url): FindSimilarBuilder;

    /**
     * Start building a contents request.
     *
     * @param  array<string>  $ids
     */
    public function contents(array $ids): ContentsBuilder;

    /**
     * Start building an answer request.
     */
    public function answer(string $query): AnswerBuilder;
}
