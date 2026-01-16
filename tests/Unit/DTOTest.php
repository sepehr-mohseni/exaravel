<?php

declare(strict_types=1);

use Sepehr_Mohseni\Exaravel\DTOs\AnswerResponse;
use Sepehr_Mohseni\Exaravel\DTOs\Citation;
use Sepehr_Mohseni\Exaravel\DTOs\ContentsResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ExaResponse;
use Sepehr_Mohseni\Exaravel\DTOs\PageContent;
use Sepehr_Mohseni\Exaravel\DTOs\SearchResult;

describe('SearchResult DTO', function (): void {
    it('creates from array', function (): void {
        $data = [
            'id' => 'test-id',
            'url' => 'https://example.com',
            'title' => 'Test Title',
            'publishedDate' => '2024-01-15T10:30:00Z',
            'author' => 'John Doe',
            'score' => 0.95,
            'text' => 'Some content text',
            'highlights' => ['highlight 1', 'highlight 2'],
        ];

        $result = SearchResult::fromArray($data);

        expect($result->id)->toBe('test-id')
            ->and($result->url)->toBe('https://example.com')
            ->and($result->title)->toBe('Test Title')
            ->and($result->author)->toBe('John Doe')
            ->and($result->score)->toBe(0.95)
            ->and($result->text)->toBe('Some content text')
            ->and($result->highlights)->toHaveCount(2);
    });

    it('handles nullable fields', function (): void {
        $data = [
            'id' => 'minimal-id',
            'url' => 'https://example.com',
        ];

        $result = SearchResult::fromArray($data);

        expect($result->title)->toBeNull()
            ->and($result->author)->toBeNull()
            ->and($result->score)->toBeNull()
            ->and($result->text)->toBeNull();
    });

    it('checks for content', function (): void {
        $withText = SearchResult::fromArray([
            'id' => '1',
            'url' => 'https://example.com',
            'text' => 'Some text',
        ]);

        $withHighlights = SearchResult::fromArray([
            'id' => '2',
            'url' => 'https://example.com',
            'highlights' => ['highlight'],
        ]);

        $withoutContent = SearchResult::fromArray([
            'id' => '3',
            'url' => 'https://example.com',
        ]);

        expect($withText->hasContent())->toBeTrue()
            ->and($withHighlights->hasContent())->toBeTrue()
            ->and($withoutContent->hasContent())->toBeFalse();
    });

    it('converts to array', function (): void {
        $result = SearchResult::fromArray([
            'id' => 'test',
            'url' => 'https://example.com',
            'title' => 'Test',
            'score' => 0.9,
        ]);

        $array = $result->toArray();

        expect($array)
            ->toHaveKey('id', 'test')
            ->toHaveKey('url', 'https://example.com')
            ->toHaveKey('title', 'Test')
            ->toHaveKey('score', 0.9)
            ->not->toHaveKey('author')
            ->not->toHaveKey('text');
    });
});

describe('ExaResponse DTO', function (): void {
    it('creates from array with multiple results', function (): void {
        $data = [
            'results' => [
                ['id' => '1', 'url' => 'https://example.com/1', 'title' => 'First'],
                ['id' => '2', 'url' => 'https://example.com/2', 'title' => 'Second'],
                ['id' => '3', 'url' => 'https://example.com/3', 'title' => 'Third'],
            ],
            'autopromptString' => '  optimized query  ',
        ];

        $response = ExaResponse::fromArray($data, 'req-123');

        expect($response->count())->toBe(3)
            ->and($response->getRequestId())->toBe('req-123')
            ->and($response->getAutopromptString())->toBe('optimized query')
            ->and($response->isEmpty())->toBeFalse()
            ->and($response->isNotEmpty())->toBeTrue();
    });

    it('handles empty results', function (): void {
        $response = ExaResponse::fromArray(['results' => []]);

        expect($response->isEmpty())->toBeTrue()
            ->and($response->count())->toBe(0)
            ->and($response->first())->toBeNull()
            ->and($response->last())->toBeNull();
    });

    it('provides first and last results', function (): void {
        $data = [
            'results' => [
                ['id' => 'first', 'url' => 'https://example.com/1'],
                ['id' => 'middle', 'url' => 'https://example.com/2'],
                ['id' => 'last', 'url' => 'https://example.com/3'],
            ],
        ];

        $response = ExaResponse::fromArray($data);

        expect($response->first()->id)->toBe('first')
            ->and($response->last()->id)->toBe('last')
            ->and($response->get(1)->id)->toBe('middle')
            ->and($response->get(10))->toBeNull();
    });

    it('is iterable', function (): void {
        $data = [
            'results' => [
                ['id' => '1', 'url' => 'https://example.com/1'],
                ['id' => '2', 'url' => 'https://example.com/2'],
            ],
        ];

        $response = ExaResponse::fromArray($data);
        $ids = [];

        foreach ($response as $result) {
            $ids[] = $result->id;
        }

        expect($ids)->toBe(['1', '2']);
    });

    it('supports map and filter', function (): void {
        $data = [
            'results' => [
                ['id' => '1', 'url' => 'https://a.com', 'score' => 0.9],
                ['id' => '2', 'url' => 'https://b.com', 'score' => 0.7],
                ['id' => '3', 'url' => 'https://c.com', 'score' => 0.85],
            ],
        ];

        $response = ExaResponse::fromArray($data);

        $urls = $response->urls();
        $highScoring = $response->filter(fn ($r) => $r->score > 0.8);
        $mapped = $response->map(fn ($r) => $r->id);

        expect($urls)->toBe(['https://a.com', 'https://b.com', 'https://c.com'])
            ->and($highScoring)->toHaveCount(2)
            ->and($mapped)->toBe(['1', '2', '3']);
    });
});

describe('AnswerResponse DTO', function (): void {
    it('creates from array with citations', function (): void {
        $data = [
            'answer' => 'This is the answer text.',
            'citations' => [
                ['id' => 'cite-1', 'url' => 'https://source1.com', 'title' => 'Source 1'],
                ['id' => 'cite-2', 'url' => 'https://source2.com', 'title' => 'Source 2'],
            ],
        ];

        $response = AnswerResponse::fromArray($data, 'req-456');

        expect($response->answer)->toBe('This is the answer text.')
            ->and($response->hasCitations())->toBeTrue()
            ->and($response->citationCount())->toBe(2)
            ->and($response->citationUrls())->toBe(['https://source1.com', 'https://source2.com'])
            ->and($response->requestId)->toBe('req-456');
    });

    it('handles response without citations', function (): void {
        $data = [
            'answer' => 'Answer without sources.',
            'citations' => [],
        ];

        $response = AnswerResponse::fromArray($data);

        expect($response->hasCitations())->toBeFalse()
            ->and($response->citationCount())->toBe(0);
    });
});

describe('Citation DTO', function (): void {
    it('creates from array', function (): void {
        $data = [
            'id' => 'citation-id',
            'url' => 'https://example.com',
            'title' => 'Citation Title',
            'publishedDate' => '2024-01-01',
            'text' => 'Cited text content',
            'highlights' => ['key phrase'],
        ];

        $citation = Citation::fromArray($data);

        expect($citation->id)->toBe('citation-id')
            ->and($citation->url)->toBe('https://example.com')
            ->and($citation->title)->toBe('Citation Title')
            ->and($citation->text)->toBe('Cited text content')
            ->and($citation->highlights)->toHaveCount(1);
    });
});

describe('PageContent DTO', function (): void {
    it('creates from array', function (): void {
        $data = [
            'id' => 'page-id',
            'url' => 'https://example.com',
            'title' => 'Page Title',
            'text' => 'Full page text content',
            'markdown' => '# Page Title\n\nContent...',
            'summary' => 'Brief summary',
        ];

        $content = PageContent::fromArray($data);

        expect($content->id)->toBe('page-id')
            ->and($content->hasText())->toBeTrue()
            ->and($content->hasMarkdown())->toBeTrue()
            ->and($content->getPreferredContent())->toBe('# Page Title\n\nContent...');
    });

    it('prefers markdown over text', function (): void {
        $withBoth = PageContent::fromArray([
            'id' => '1',
            'url' => 'https://example.com',
            'text' => 'Plain text',
            'markdown' => '# Markdown',
        ]);

        $textOnly = PageContent::fromArray([
            'id' => '2',
            'url' => 'https://example.com',
            'text' => 'Only text',
        ]);

        expect($withBoth->getPreferredContent())->toBe('# Markdown')
            ->and($textOnly->getPreferredContent())->toBe('Only text');
    });
});

describe('ContentsResponse DTO', function (): void {
    it('creates from array', function (): void {
        $data = [
            'results' => [
                ['id' => '1', 'url' => 'https://example.com/1', 'text' => 'Text 1'],
                ['id' => '2', 'url' => 'https://example.com/2', 'markdown' => '# Markdown'],
            ],
        ];

        $response = ContentsResponse::fromArray($data, 'req-789');

        expect($response->count())->toBe(2)
            ->and($response->getRequestId())->toBe('req-789')
            ->and($response->texts())->toBe(['Text 1', null])
            ->and($response->markdowns())->toBe([null, '# Markdown']);
    });
});
