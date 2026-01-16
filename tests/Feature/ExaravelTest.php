<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Sepehr_Mohseni\Exaravel\DTOs\AnswerResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ContentsResponse;
use Sepehr_Mohseni\Exaravel\DTOs\ExaResponse;
use Sepehr_Mohseni\Exaravel\Enums\Category;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;
use Sepehr_Mohseni\Exaravel\ExaManager;
use Sepehr_Mohseni\Exaravel\Exceptions\AuthenticationException;
use Sepehr_Mohseni\Exaravel\Exceptions\InsufficientCreditsException;
use Sepehr_Mohseni\Exaravel\Exceptions\RateLimitException;
use Sepehr_Mohseni\Exaravel\Exceptions\ValidationException;
use Sepehr_Mohseni\Exaravel\Facades\Exa;

beforeEach(function (): void {
    Http::preventStrayRequests();

    // Clear cached drivers in the manager so new config is picked up
    // The API key is already set in TestCase::defineEnvironment
    app(ExaManager::class)->forgetDrivers();
});

describe('Search Endpoint', function (): void {
    it('performs a basic search', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'results' => [
                    [
                        'id' => 'result-1',
                        'url' => 'https://example.com/article-1',
                        'title' => 'Test Article 1',
                        'score' => 0.95,
                        'publishedDate' => '2024-01-15T10:30:00Z',
                    ],
                    [
                        'id' => 'result-2',
                        'url' => 'https://example.com/article-2',
                        'title' => 'Test Article 2',
                        'score' => 0.89,
                    ],
                ],
                'autopromptString' => 'optimized query string',
            ], 200, ['x-request-id' => 'req-12345']),
        ]);

        $response = Exa::search('Laravel best practices')->get();

        expect($response)
            ->toBeInstanceOf(ExaResponse::class)
            ->and($response->count())->toBe(2)
            ->and($response->first()->title)->toBe('Test Article 1')
            ->and($response->first()->url)->toBe('https://example.com/article-1')
            ->and($response->getAutopromptString())->toBe('optimized query string');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.exa.ai/search'
            && $request['query'] === 'Laravel best practices'
        );
    });

    it('performs search with fluent builder options', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'results' => [
                    [
                        'id' => 'result-1',
                        'url' => 'https://github.com/laravel/framework',
                        'title' => 'Laravel Framework',
                        'text' => 'Laravel is a web application framework...',
                        'highlights' => ['Laravel is powerful', 'framework for PHP'],
                    ],
                ],
            ], 200),
        ]);

        $response = Exa::search('Laravel framework')
            ->type(SearchType::Neural)
            ->category(Category::GitHub)
            ->numResults(5)
            ->includeDomains(['github.com'])
            ->excludeDomains(['stackoverflow.com'])
            ->startPublishedDate('2024-01-01')
            ->endPublishedDate('2024-12-31')
            ->withContents(text: true, highlights: true)
            ->withAutoprompt()
            ->get();

        expect($response->count())->toBe(1)
            ->and($response->first()->hasContent())->toBeTrue();

        Http::assertSent(fn ($request) => $request['type'] === 'neural'
            && $request['category'] === 'github'
            && $request['numResults'] === 5
            && $request['includeDomains'] === ['github.com']
            && $request['excludeDomains'] === ['stackoverflow.com']
            && $request['useAutoprompt'] === true
            && isset($request['contents'])
        );
    });

    it('searches with date range using DateTime objects', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response(['results' => []], 200),
        ]);

        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-06-30');

        Exa::search('PHP 8.4 features')
            ->publishedBetween($startDate, $endDate)
            ->get();

        Http::assertSent(fn ($request) => $request['startPublishedDate'] === '2024-01-01T00:00:00Z'
            && $request['endPublishedDate'] === '2024-06-30T00:00:00Z'
        );
    });

    it('returns first result directly', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'results' => [
                    [
                        'id' => 'first-result',
                        'url' => 'https://example.com/first',
                        'title' => 'First Result',
                    ],
                ],
            ], 200),
        ]);

        $result = Exa::search('test query')->first();

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe('first-result');
    });
});

describe('Find Similar Endpoint', function (): void {
    it('finds similar content to a URL', function (): void {
        Http::fake([
            'api.exa.ai/findSimilar' => Http::response([
                'results' => [
                    [
                        'id' => 'similar-1',
                        'url' => 'https://similar-site.com/article',
                        'title' => 'Similar Article',
                        'score' => 0.92,
                    ],
                    [
                        'id' => 'similar-2',
                        'url' => 'https://another-site.com/post',
                        'title' => 'Another Similar Post',
                        'score' => 0.87,
                    ],
                ],
            ], 200),
        ]);

        $response = Exa::findSimilar('https://laravel.com/docs')
            ->numResults(10)
            ->excludeSourceDomain()
            ->get();

        expect($response)->toBeInstanceOf(ExaResponse::class)
            ->and($response->count())->toBe(2)
            ->and($response->first()->score)->toBe(0.92);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.exa.ai/findSimilar'
            && $request['url'] === 'https://laravel.com/docs'
            && $request['excludeSourceDomain'] === true
        );
    });

    it('finds similar with content options', function (): void {
        Http::fake([
            'api.exa.ai/findSimilar' => Http::response([
                'results' => [
                    [
                        'id' => 'result-1',
                        'url' => 'https://example.com',
                        'text' => 'Full text content here...',
                        'summary' => 'A brief summary.',
                    ],
                ],
            ], 200),
        ]);

        $response = Exa::findSimilar('https://example.com/original')
            ->withContents(text: true, summary: true)
            ->category(Category::News)
            ->get();

        expect($response->first()->text)->toBe('Full text content here...');

        Http::assertSent(fn ($request) => isset($request['contents'])
            && $request['category'] === 'news'
        );
    });
});

describe('Contents Endpoint', function (): void {
    it('retrieves content for multiple IDs', function (): void {
        Http::fake([
            'api.exa.ai/contents' => Http::response([
                'results' => [
                    [
                        'id' => 'doc-1',
                        'url' => 'https://example.com/doc1',
                        'title' => 'Document 1',
                        'text' => 'Full text of document 1...',
                    ],
                    [
                        'id' => 'doc-2',
                        'url' => 'https://example.com/doc2',
                        'title' => 'Document 2',
                        'text' => 'Full text of document 2...',
                    ],
                ],
            ], 200, ['x-request-id' => 'content-req-456']),
        ]);

        $response = Exa::contents(['doc-1', 'doc-2'])
            ->withText(maxCharacters: 5000)
            ->get();

        expect($response)
            ->toBeInstanceOf(ContentsResponse::class)
            ->and($response->count())->toBe(2)
            ->and($response->getRequestId())->toBe('content-req-456');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.exa.ai/contents'
            && $request['ids'] === ['doc-1', 'doc-2']
        );
    });

    it('retrieves markdown content', function (): void {
        Http::fake([
            'api.exa.ai/contents' => Http::response([
                'results' => [
                    [
                        'id' => 'doc-1',
                        'url' => 'https://example.com/doc1',
                        'markdown' => '# Heading\n\nParagraph content...',
                    ],
                ],
            ], 200),
        ]);

        $response = Exa::contents(['doc-1'])
            ->withMarkdown()
            ->withoutText()
            ->get();

        expect($response->first()->hasMarkdown())->toBeTrue()
            ->and($response->first()->markdown)->toContain('# Heading');

        Http::assertSent(fn ($request) => isset($request['markdown'])
        );
    });

    it('retrieves highlights and summary', function (): void {
        Http::fake([
            'api.exa.ai/contents' => Http::response([
                'results' => [
                    [
                        'id' => 'doc-1',
                        'url' => 'https://example.com',
                        'highlights' => ['Important sentence 1', 'Key phrase 2'],
                        'summary' => 'Brief summary of the content.',
                    ],
                ],
            ], 200),
        ]);

        $response = Exa::contents(['doc-1'])
            ->withHighlights(perUrl: 3)
            ->withSummary('What is this about?')
            ->get();

        $content = $response->first();

        expect($content->hasHighlights())->toBeTrue()
            ->and($content->highlights)->toHaveCount(2)
            ->and($content->summary)->toBe('Brief summary of the content.');
    });
});

describe('Answer Endpoint', function (): void {
    it('gets an answer with citations', function (): void {
        Http::fake([
            'api.exa.ai/answer' => Http::response([
                'answer' => 'Laravel is a PHP web application framework with expressive, elegant syntax. It provides tools for routing, authentication, and caching.',
                'citations' => [
                    [
                        'id' => 'cite-1',
                        'url' => 'https://laravel.com/docs',
                        'title' => 'Laravel Documentation',
                        'text' => 'Laravel is a web application framework...',
                    ],
                    [
                        'id' => 'cite-2',
                        'url' => 'https://github.com/laravel/laravel',
                        'title' => 'Laravel GitHub',
                    ],
                ],
            ], 200, ['x-request-id' => 'answer-req-789']),
        ]);

        $response = Exa::answer('What is Laravel?')
            ->numResults(5)
            ->neural()
            ->withAutoprompt()
            ->get();

        expect($response)
            ->toBeInstanceOf(AnswerResponse::class)
            ->and($response->answer)->toContain('Laravel is a PHP')
            ->and($response->hasCitations())->toBeTrue()
            ->and($response->citationCount())->toBe(2)
            ->and($response->citationUrls())->toContain('https://laravel.com/docs');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.exa.ai/answer'
            && $request['query'] === 'What is Laravel?'
            && $request['type'] === 'neural'
        );
    });

    it('gets answer with custom system prompt', function (): void {
        Http::fake([
            'api.exa.ai/answer' => Http::response([
                'answer' => 'Technical explanation here...',
                'citations' => [],
            ], 200),
        ]);

        $response = Exa::answer('Explain PHP 8.4 features')
            ->systemPrompt('You are a technical documentation expert. Provide detailed explanations.')
            ->includeDomains(['php.net', 'wiki.php.net'])
            ->get();

        Http::assertSent(fn ($request) => $request['systemPrompt'] === 'You are a technical documentation expert. Provide detailed explanations.'
            && $request['includeDomains'] === ['php.net', 'wiki.php.net']
        );
    });

    it('returns answer text directly', function (): void {
        Http::fake([
            'api.exa.ai/answer' => Http::response([
                'answer' => 'Direct answer text.',
                'citations' => [],
            ], 200),
        ]);

        $text = Exa::answer('Simple question')->text();

        expect($text)->toBe('Direct answer text.');
    });
});

describe('Error Handling', function (): void {
    it('throws AuthenticationException on 401', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'error' => 'Invalid API key',
            ], 401, ['x-request-id' => 'err-auth']),
        ]);

        expect(fn () => Exa::search('test')->get())
            ->toThrow(AuthenticationException::class);
    });

    it('throws InsufficientCreditsException on 402', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'error' => 'Insufficient credits',
            ], 402),
        ]);

        expect(fn () => Exa::search('test')->get())
            ->toThrow(InsufficientCreditsException::class);
    });

    it('throws ValidationException on 400 with errors', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'message' => 'Validation failed',
                'errors' => ['query' => 'Query is required'],
            ], 400),
        ]);

        expect(fn () => Exa::search('')->get())
            ->toThrow(ValidationException::class);
    });

    it('throws RateLimitException on 429 with retry-after', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'error' => 'Rate limit exceeded',
            ], 429, ['retry-after' => '60', 'x-request-id' => 'rate-limit-req']),
        ]);

        try {
            Exa::search('test')->get();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            expect($e->getRetryAfter())->toBe(60)
                ->and($e->getRequestId())->toBe('rate-limit-req');
        }
    });
});

describe('Multiple Connections', function (): void {
    it('supports multiple API key configurations', function (): void {
        config([
            'exaravel.connections' => [
                'default' => ['api_key' => 'default-key'],
                'secondary' => ['api_key' => 'secondary-key'],
            ],
        ]);

        Http::fake([
            'api.exa.ai/search' => Http::response(['results' => []], 200),
        ]);

        Exa::using('secondary')->search('test query')->get();

        Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'secondary-key')
        );
    });

    it('creates client with custom API key', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response(['results' => []], 200),
        ]);

        Exa::withApiKey('custom-api-key')->search('test')->get();

        Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'custom-api-key')
        );
    });
});

describe('Response Iteration', function (): void {
    it('iterates over results', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'results' => [
                    ['id' => '1', 'url' => 'https://example.com/1', 'title' => 'Result 1'],
                    ['id' => '2', 'url' => 'https://example.com/2', 'title' => 'Result 2'],
                    ['id' => '3', 'url' => 'https://example.com/3', 'title' => 'Result 3'],
                ],
            ], 200),
        ]);

        $response = Exa::search('test')->get();

        $titles = [];
        foreach ($response as $result) {
            $titles[] = $result->title;
        }

        expect($titles)->toBe(['Result 1', 'Result 2', 'Result 3']);
    });

    it('maps and filters results', function (): void {
        Http::fake([
            'api.exa.ai/search' => Http::response([
                'results' => [
                    ['id' => '1', 'url' => 'https://example.com/1', 'score' => 0.9],
                    ['id' => '2', 'url' => 'https://example.com/2', 'score' => 0.7],
                    ['id' => '3', 'url' => 'https://example.com/3', 'score' => 0.85],
                ],
            ], 200),
        ]);

        $response = Exa::search('test')->get();

        $urls = $response->urls();
        $highScoring = $response->filter(fn ($r) => $r->score > 0.8);

        expect($urls)->toHaveCount(3)
            ->and($highScoring)->toHaveCount(2);
    });
});
