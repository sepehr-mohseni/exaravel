<?php

declare(strict_types=1);

use Sepehr_Mohseni\Exaravel\Builders\SearchBuilder;
use Sepehr_Mohseni\Exaravel\Enums\Category;
use Sepehr_Mohseni\Exaravel\Enums\LivecrawlMode;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;
use Sepehr_Mohseni\Exaravel\ExaClient;

/**
 * Create a real client instance for testing.
 * We use a dummy API key since we're only testing payload building, not actual API calls.
 */
function createTestClient(): ExaClient
{
    return new ExaClient(apiKey: 'test-api-key-for-unit-tests');
}

describe('SearchBuilder Payload', function (): void {
    it('builds basic search payload', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test query');

        $payload = $builder->toPayload();

        expect($payload)
            ->toHaveKey('query', 'test query')
            ->toHaveKey('type', 'auto')
            ->toHaveKey('numResults')
            ->toHaveKey('useAutoprompt', false);
    });

    it('builds payload with search type', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder->neural()->toPayload();
        expect($payload['type'])->toBe('neural');

        $payload = $builder->keyword()->toPayload();
        expect($payload['type'])->toBe('keyword');

        $payload = $builder->type(SearchType::Auto)->toPayload();
        expect($payload['type'])->toBe('auto');
    });

    it('builds payload with category', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder->category(Category::GitHub)->toPayload();
        expect($payload['category'])->toBe('github');

        $payload = $builder->category('news')->toPayload();
        expect($payload['category'])->toBe('news');
    });

    it('builds payload with domain filters', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder
            ->includeDomains(['github.com', 'gitlab.com'])
            ->excludeDomains('stackoverflow.com')
            ->toPayload();

        expect($payload['includeDomains'])->toBe(['github.com', 'gitlab.com'])
            ->and($payload['excludeDomains'])->toBe(['stackoverflow.com']);
    });

    it('builds payload with date filters', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder
            ->startPublishedDate('2024-01-01')
            ->endPublishedDate('2024-12-31')
            ->startCrawlDate('2024-06-01')
            ->endCrawlDate('2024-06-30')
            ->toPayload();

        expect($payload)
            ->toHaveKey('startPublishedDate', '2024-01-01')
            ->toHaveKey('endPublishedDate', '2024-12-31')
            ->toHaveKey('startCrawlDate', '2024-06-01')
            ->toHaveKey('endCrawlDate', '2024-06-30');
    });

    it('builds payload with DateTime objects', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $start = new DateTimeImmutable('2024-03-15 10:30:00');
        $end = new DateTimeImmutable('2024-06-15 14:45:00');

        $payload = $builder
            ->startPublishedDate($start)
            ->endPublishedDate($end)
            ->toPayload();

        expect($payload['startPublishedDate'])->toBe('2024-03-15T10:30:00Z')
            ->and($payload['endPublishedDate'])->toBe('2024-06-15T14:45:00Z');
    });

    it('builds payload with text filters', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder
            ->includeText(['Laravel', 'PHP'])
            ->excludeText('outdated')
            ->toPayload();

        expect($payload['includeText'])->toBe(['Laravel', 'PHP'])
            ->and($payload['excludeText'])->toBe(['outdated']);
    });

    it('builds payload with contents options', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder
            ->withContents(text: true, highlights: true, summary: true, maxCharacters: 5000)
            ->toPayload();

        expect($payload)->toHaveKey('contents')
            ->and($payload['contents'])->toHaveKey('text')
            ->and($payload['contents'])->toHaveKey('highlights')
            ->and($payload['contents'])->toHaveKey('summary');
    });

    it('builds payload with livecrawl options', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder
            ->livecrawl(LivecrawlMode::Always, timeout: 10000)
            ->toPayload();

        expect($payload['livecrawl'])->toBe('always')
            ->and($payload['livecrawlTimeout'])->toBe(10000);
    });

    it('limits numResults to valid range', function (): void {
        $client = createTestClient();
        $builder = new SearchBuilder($client, 'test');

        $payload = $builder->numResults(150)->toPayload();
        expect($payload['numResults'])->toBe(100);

        $payload = $builder->numResults(-5)->toPayload();
        expect($payload['numResults'])->toBe(1);
    });
});
