<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Builders;

use Sepehr_Mohseni\Exaravel\Contracts\BuilderInterface;
use Sepehr_Mohseni\Exaravel\DTOs\ExaResponse;
use Sepehr_Mohseni\Exaravel\Enums\Category;
use Sepehr_Mohseni\Exaravel\Enums\LivecrawlMode;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;
use Sepehr_Mohseni\Exaravel\ExaClient;

/**
 * Fluent builder for Exa.ai search requests.
 */
final class SearchBuilder implements BuilderInterface
{
    private SearchType $type;

    private ?Category $category = null;

    private int $numResults;

    private bool $useAutoprompt = false;

    /** @var array<string> */
    private array $includeDomains = [];

    /** @var array<string> */
    private array $excludeDomains = [];

    private ?string $startPublishedDate = null;

    private ?string $endPublishedDate = null;

    private ?string $startCrawlDate = null;

    private ?string $endCrawlDate = null;

    /** @var array<string> */
    private array $includeText = [];

    /** @var array<string> */
    private array $excludeText = [];

    // Content options
    private bool $includeContents = false;

    private bool $includeText_content = true;

    private bool $includeHighlights = false;

    private bool $includeSummary = false;

    private ?int $maxCharacters = null;

    private ?int $highlightsPerUrl = null;

    private ?int $highlightWords = null;

    private ?string $summaryQuery = null;

    // Livecrawl options
    private ?LivecrawlMode $livecrawl = null;

    private ?int $livecrawlTimeout = null;

    public function __construct(
        private readonly ExaClient $client,
        private readonly string $query,
    ) {
        $this->type = SearchType::fromString(
            config('exaravel.default_search_type', 'auto')
        );
        $this->numResults = (int) config('exaravel.default_num_results', 10);
    }

    /**
     * Set the search type.
     */
    public function type(SearchType|string $type): self
    {
        $this->type = $type instanceof SearchType ? $type : SearchType::fromString($type);

        return $this;
    }

    /**
     * Use neural search type.
     */
    public function neural(): self
    {
        return $this->type(SearchType::Neural);
    }

    /**
     * Use keyword search type.
     */
    public function keyword(): self
    {
        return $this->type(SearchType::Keyword);
    }

    /**
     * Use auto search type.
     */
    public function auto(): self
    {
        return $this->type(SearchType::Auto);
    }

    /**
     * Set the content category.
     */
    public function category(Category|string $category): self
    {
        $this->category = $category instanceof Category
            ? $category
            : Category::from($category);

        return $this;
    }

    /**
     * Set the number of results.
     */
    public function numResults(int $numResults): self
    {
        $this->numResults = max(1, min($numResults, 100));

        return $this;
    }

    /**
     * Alias for numResults.
     */
    public function limit(int $limit): self
    {
        return $this->numResults($limit);
    }

    /**
     * Enable autoprompt.
     */
    public function withAutoprompt(bool $enabled = true): self
    {
        $this->useAutoprompt = $enabled;

        return $this;
    }

    /**
     * Add domains to include.
     *
     * @param  string|array<string>  $domains
     */
    public function includeDomains(string|array $domains): self
    {
        $domains = is_array($domains) ? $domains : [$domains];
        $this->includeDomains = array_merge($this->includeDomains, $domains);

        return $this;
    }

    /**
     * Add domains to exclude.
     *
     * @param  string|array<string>  $domains
     */
    public function excludeDomains(string|array $domains): self
    {
        $domains = is_array($domains) ? $domains : [$domains];
        $this->excludeDomains = array_merge($this->excludeDomains, $domains);

        return $this;
    }

    /**
     * Set start published date filter.
     */
    public function startPublishedDate(string|\DateTimeInterface $date): self
    {
        $this->startPublishedDate = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d\TH:i:s\Z')
            : $date;

        return $this;
    }

    /**
     * Set end published date filter.
     */
    public function endPublishedDate(string|\DateTimeInterface $date): self
    {
        $this->endPublishedDate = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d\TH:i:s\Z')
            : $date;

        return $this;
    }

    /**
     * Set published date range.
     */
    public function publishedBetween(
        string|\DateTimeInterface $start,
        string|\DateTimeInterface $end
    ): self {
        return $this->startPublishedDate($start)->endPublishedDate($end);
    }

    /**
     * Set start crawl date filter.
     */
    public function startCrawlDate(string|\DateTimeInterface $date): self
    {
        $this->startCrawlDate = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d\TH:i:s\Z')
            : $date;

        return $this;
    }

    /**
     * Set end crawl date filter.
     */
    public function endCrawlDate(string|\DateTimeInterface $date): self
    {
        $this->endCrawlDate = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d\TH:i:s\Z')
            : $date;

        return $this;
    }

    /**
     * Add text to include in results.
     *
     * @param  string|array<string>  $text
     */
    public function includeText(string|array $text): self
    {
        $text = is_array($text) ? $text : [$text];
        $this->includeText = array_merge($this->includeText, $text);

        return $this;
    }

    /**
     * Add text to exclude from results.
     *
     * @param  string|array<string>  $text
     */
    public function excludeText(string|array $text): self
    {
        $text = is_array($text) ? $text : [$text];
        $this->excludeText = array_merge($this->excludeText, $text);

        return $this;
    }

    /**
     * Include page contents in results.
     */
    public function withContents(
        bool $text = true,
        bool $highlights = false,
        bool $summary = false,
        ?int $maxCharacters = null,
    ): self {
        $this->includeContents = true;
        $this->includeText_content = $text;
        $this->includeHighlights = $highlights;
        $this->includeSummary = $summary;
        $this->maxCharacters = $maxCharacters;

        return $this;
    }

    /**
     * Include highlights in results.
     */
    public function withHighlights(?int $perUrl = null, ?int $words = null): self
    {
        $this->includeContents = true;
        $this->includeHighlights = true;
        $this->highlightsPerUrl = $perUrl;
        $this->highlightWords = $words;

        return $this;
    }

    /**
     * Include summary in results.
     */
    public function withSummary(?string $query = null): self
    {
        $this->includeContents = true;
        $this->includeSummary = true;
        $this->summaryQuery = $query;

        return $this;
    }

    /**
     * Set livecrawl mode.
     */
    public function livecrawl(LivecrawlMode|string $mode, ?int $timeout = null): self
    {
        $this->livecrawl = $mode instanceof LivecrawlMode
            ? $mode
            : LivecrawlMode::from($mode);
        $this->livecrawlTimeout = $timeout;

        return $this;
    }

    /**
     * Build the request payload.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [
            'query' => $this->query,
            'type' => $this->type->value,
            'numResults' => $this->numResults,
            'useAutoprompt' => $this->useAutoprompt,
        ];

        if ($this->category !== null) {
            $payload['category'] = $this->category->value;
        }

        if (! empty($this->includeDomains)) {
            $payload['includeDomains'] = $this->includeDomains;
        }

        if (! empty($this->excludeDomains)) {
            $payload['excludeDomains'] = $this->excludeDomains;
        }

        if ($this->startPublishedDate !== null) {
            $payload['startPublishedDate'] = $this->startPublishedDate;
        }

        if ($this->endPublishedDate !== null) {
            $payload['endPublishedDate'] = $this->endPublishedDate;
        }

        if ($this->startCrawlDate !== null) {
            $payload['startCrawlDate'] = $this->startCrawlDate;
        }

        if ($this->endCrawlDate !== null) {
            $payload['endCrawlDate'] = $this->endCrawlDate;
        }

        if (! empty($this->includeText)) {
            $payload['includeText'] = $this->includeText;
        }

        if (! empty($this->excludeText)) {
            $payload['excludeText'] = $this->excludeText;
        }

        if ($this->includeContents) {
            $payload['contents'] = $this->buildContentsOptions();
        }

        if ($this->livecrawl !== null) {
            $payload['livecrawl'] = $this->livecrawl->value;

            if ($this->livecrawlTimeout !== null) {
                $payload['livecrawlTimeout'] = $this->livecrawlTimeout;
            }
        }

        return $payload;
    }

    /**
     * Build contents options for the payload.
     *
     * @return array<string, mixed>
     */
    private function buildContentsOptions(): array
    {
        $options = [];

        if ($this->includeText_content) {
            $textOptions = true;

            if ($this->maxCharacters !== null) {
                $textOptions = ['maxCharacters' => $this->maxCharacters];
            }

            $options['text'] = $textOptions;
        }

        if ($this->includeHighlights) {
            $highlightOptions = true;

            if ($this->highlightsPerUrl !== null || $this->highlightWords !== null) {
                $highlightOptions = array_filter([
                    'numSentences' => $this->highlightsPerUrl,
                    'highlightsPerUrl' => $this->highlightsPerUrl,
                    'query' => $this->highlightWords,
                ]);
            }

            $options['highlights'] = $highlightOptions;
        }

        if ($this->includeSummary) {
            $summaryOptions = true;

            if ($this->summaryQuery !== null) {
                $summaryOptions = ['query' => $this->summaryQuery];
            }

            $options['summary'] = $summaryOptions;
        }

        return $options;
    }

    /**
     * Execute the search request.
     */
    public function get(): ExaResponse
    {
        return $this->client->executeSearch($this);
    }

    /**
     * Execute and return first result.
     */
    public function first(): ?\Sepehr_Mohseni\Exaravel\DTOs\SearchResult
    {
        return $this->get()->first();
    }
}
