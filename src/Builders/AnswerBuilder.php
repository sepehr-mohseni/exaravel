<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Builders;

use Sepehr_Mohseni\Exaravel\Contracts\BuilderInterface;
use Sepehr_Mohseni\Exaravel\DTOs\AnswerResponse;
use Sepehr_Mohseni\Exaravel\Enums\Category;
use Sepehr_Mohseni\Exaravel\Enums\LivecrawlMode;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;
use Sepehr_Mohseni\Exaravel\ExaClient;

/**
 * Fluent builder for Exa.ai answer (agentic) requests.
 */
final class AnswerBuilder implements BuilderInterface
{
    private SearchType $type;

    private ?Category $category = null;

    private int $numResults;

    private bool $useAutoprompt = true;

    /** @var array<string> */
    private array $includeDomains = [];

    /** @var array<string> */
    private array $excludeDomains = [];

    private ?string $startPublishedDate = null;

    private ?string $endPublishedDate = null;

    /** @var array<string> */
    private array $includeText = [];

    /** @var array<string> */
    private array $excludeText = [];

    // Content options
    private bool $includeText_content = true;

    private ?int $maxCharacters = null;

    // Livecrawl options
    private ?LivecrawlMode $livecrawl = null;

    private ?int $livecrawlTimeout = null;

    // Answer-specific options
    private ?string $systemPrompt = null;

    private ?string $model = null;

    public function __construct(
        private readonly ExaClient $client,
        private readonly string $query,
    ) {
        $this->type = SearchType::Auto;
        $this->numResults = 5;
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
     * Set the number of results to use for the answer.
     */
    public function numResults(int $numResults): self
    {
        $this->numResults = max(1, min($numResults, 20));

        return $this;
    }

    /**
     * Enable or disable autoprompt.
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
     * Set content options.
     */
    public function withContents(bool $text = true, ?int $maxCharacters = null): self
    {
        $this->includeText_content = $text;
        $this->maxCharacters = $maxCharacters;

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
     * Set custom system prompt for the answer.
     */
    public function systemPrompt(string $prompt): self
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    /**
     * Set the model to use for generating the answer.
     */
    public function model(string $model): self
    {
        $this->model = $model;

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

        if (! empty($this->includeText)) {
            $payload['includeText'] = $this->includeText;
        }

        if (! empty($this->excludeText)) {
            $payload['excludeText'] = $this->excludeText;
        }

        if ($this->includeText_content) {
            $textOptions = true;

            if ($this->maxCharacters !== null) {
                $textOptions = ['maxCharacters' => $this->maxCharacters];
            }

            $payload['text'] = $textOptions;
        }

        if ($this->livecrawl !== null) {
            $payload['livecrawl'] = $this->livecrawl->value;

            if ($this->livecrawlTimeout !== null) {
                $payload['livecrawlTimeout'] = $this->livecrawlTimeout;
            }
        }

        if ($this->systemPrompt !== null) {
            $payload['systemPrompt'] = $this->systemPrompt;
        }

        if ($this->model !== null) {
            $payload['model'] = $this->model;
        }

        return $payload;
    }

    /**
     * Execute the answer request.
     */
    public function get(): AnswerResponse
    {
        return $this->client->executeAnswer($this);
    }

    /**
     * Execute and return just the answer text.
     */
    public function text(): string
    {
        return $this->get()->answer;
    }
}
