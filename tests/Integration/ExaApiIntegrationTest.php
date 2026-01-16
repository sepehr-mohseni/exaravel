<?php

declare(strict_types=1);

/**
 * Integration tests that use the real Exa.ai API.
 *
 * These tests require a valid API key configured in the test environment.
 * Run with: vendor/bin/pest tests/Integration
 *
 * Note: These tests make actual API calls and may consume credits.
 */

use Sepehr_Mohseni\Exaravel\DTOs\AnswerResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ContentsResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ExaResponse;
use Sepehr_Mohseni\Exaravel\Enums\Category;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;
use Sepehr_Mohseni\Exaravel\Facades\Exa;

uses(\Sepehr_Mohseni\Exaravel\Tests\TestCase::class)->in(__DIR__);

describe('Integration: Search Endpoint', function (): void {
    it('performs a real search query', function (): void {
        $response = Exa::search('Laravel framework PHP')
            ->type(SearchType::Auto)
            ->numResults(5)
            ->get();

        expect($response)
            ->toBeInstanceOf(ExaResponse::class)
            ->and($response->isNotEmpty())->toBeTrue()
            ->and($response->count())->toBeGreaterThanOrEqual(1)
            ->and($response->first()->url)->toBeString();
    })->group('integration');

    it('searches with neural type and includes contents', function (): void {
        $response = Exa::search('What is dependency injection in PHP?')
            ->neural()
            ->numResults(3)
            ->withContents(text: true)
            ->get();

        expect($response->isNotEmpty())->toBeTrue()
            ->and($response->first()->hasContent())->toBeTrue();
    })->group('integration');

    it('searches with domain filters', function (): void {
        $response = Exa::search('Laravel')
            ->includeDomains(['github.com'])
            ->numResults(3)
            ->get();

        expect($response->isNotEmpty())->toBeTrue();

        foreach ($response as $result) {
            expect($result->url)->toContain('github.com');
        }
    })->group('integration');

    it('searches with date filters', function (): void {
        $response = Exa::search('PHP 8.3 features')
            ->startPublishedDate('2023-01-01')
            ->numResults(3)
            ->get();

        expect($response)->toBeInstanceOf(ExaResponse::class);
    })->group('integration');

    it('searches with category filter', function (): void {
        $response = Exa::search('machine learning')
            ->category(Category::ResearchPaper)
            ->numResults(3)
            ->get();

        expect($response)->toBeInstanceOf(ExaResponse::class);
    })->group('integration');
});

describe('Integration: Find Similar Endpoint', function (): void {
    it('finds similar content to a URL', function (): void {
        $response = Exa::findSimilar('https://laravel.com')
            ->numResults(5)
            ->get();

        expect($response)
            ->toBeInstanceOf(ExaResponse::class)
            ->and($response->isNotEmpty())->toBeTrue();
    })->group('integration');

    it('finds similar with exclude source domain', function (): void {
        $response = Exa::findSimilar('https://laravel.com')
            ->numResults(3)
            ->excludeSourceDomain()
            ->get();

        expect($response->isNotEmpty())->toBeTrue();

        foreach ($response as $result) {
            expect($result->url)->not->toContain('laravel.com');
        }
    })->group('integration');
});

describe('Integration: Contents Endpoint', function (): void {
    it('retrieves content for search result IDs', function (): void {
        // First, perform a search to get some IDs
        $searchResponse = Exa::search('Laravel documentation')
            ->numResults(2)
            ->get();

        expect($searchResponse->isNotEmpty())->toBeTrue();

        $ids = $searchResponse->map(fn ($result) => $result->id);

        // Then retrieve contents
        $contentsResponse = Exa::contents($ids)
            ->withText(maxCharacters: 1000)
            ->get();

        expect($contentsResponse)
            ->toBeInstanceOf(ContentsResponse::class)
            ->and($contentsResponse->isNotEmpty())->toBeTrue();
    })->group('integration');
});

describe('Integration: Answer Endpoint', function (): void {
    it('gets an answer with citations', function (): void {
        $response = Exa::answer('What is Laravel and why is it popular?')
            ->numResults(3)
            ->get();

        expect($response)
            ->toBeInstanceOf(AnswerResponse::class)
            ->and($response->answer)->toBeString()
            ->and(strlen($response->answer))->toBeGreaterThan(10);
    })->group('integration');

    it('gets an answer with custom system prompt', function (): void {
        $response = Exa::answer('Explain PHP traits')
            ->numResults(3)
            ->systemPrompt('You are a PHP expert. Provide concise technical explanations.')
            ->get();

        expect($response->answer)->toBeString()
            ->and(strlen($response->answer))->toBeGreaterThan(10);
    })->group('integration');
});

describe('Integration: Response Methods', function (): void {
    it('correctly iterates and maps results', function (): void {
        $response = Exa::search('PHP frameworks')
            ->numResults(5)
            ->get();

        $urls = $response->urls();
        $titles = $response->titles();

        expect($urls)->toBeArray()
            ->and(count($urls))->toBe($response->count())
            ->and($titles)->toBeArray();

        $count = 0;
        foreach ($response as $result) {
            $count++;
            expect($result->url)->toBeString();
        }

        expect($count)->toBe($response->count());
    })->group('integration');

    it('filters results correctly', function (): void {
        $response = Exa::search('Laravel tutorial')
            ->numResults(10)
            ->get();

        $filtered = $response->filter(fn ($result) => $result->score !== null);

        expect(count($filtered))->toBeLessThanOrEqual($response->count());
    })->group('integration');
});
