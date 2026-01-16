<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\Builders;

use Sepehr_Mohseni\Exaravel\Contracts\BuilderInterface;
use Sepehr_Mohseni\Exaravel\DTOs\ContentsResponse;
use Sepehr_Mohseni\Exaravel\Enums\LivecrawlMode;
use Sepehr_Mohseni\Exaravel\ExaClient;

/**
 * Fluent builder for Exa.ai contents requests.
 */
final class ContentsBuilder implements BuilderInterface
{
    private bool $includeText = true;

    private bool $includeMarkdown = false;

    private bool $includeHighlights = false;

    private bool $includeSummary = false;

    private ?int $maxCharacters = null;

    private ?int $highlightsPerUrl = null;

    private ?int $highlightWords = null;

    private ?string $summaryQuery = null;

    // Livecrawl options
    private ?LivecrawlMode $livecrawl = null;

    private ?int $livecrawlTimeout = null;

    // Subpages
    private bool $includeSubpages = false;

    private ?int $subpageLimit = null;

    /**
     * @param  array<string>  $ids
     */
    public function __construct(
        private readonly ExaClient $client,
        private readonly array $ids,
    ) {}

    /**
     * Include text content.
     */
    public function withText(?int $maxCharacters = null): self
    {
        $this->includeText = true;
        $this->maxCharacters = $maxCharacters;

        return $this;
    }

    /**
     * Include markdown content.
     */
    public function withMarkdown(?int $maxCharacters = null): self
    {
        $this->includeMarkdown = true;
        $this->maxCharacters = $maxCharacters;

        return $this;
    }

    /**
     * Include highlights.
     */
    public function withHighlights(?int $perUrl = null, ?int $words = null): self
    {
        $this->includeHighlights = true;
        $this->highlightsPerUrl = $perUrl;
        $this->highlightWords = $words;

        return $this;
    }

    /**
     * Include summary.
     */
    public function withSummary(?string $query = null): self
    {
        $this->includeSummary = true;
        $this->summaryQuery = $query;

        return $this;
    }

    /**
     * Exclude text content.
     */
    public function withoutText(): self
    {
        $this->includeText = false;

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
     * Include subpages in the response.
     */
    public function withSubpages(?int $limit = null): self
    {
        $this->includeSubpages = true;
        $this->subpageLimit = $limit;

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
            'ids' => $this->ids,
        ];

        if ($this->includeText) {
            $textOptions = true;

            if ($this->maxCharacters !== null) {
                $textOptions = ['maxCharacters' => $this->maxCharacters];
            }

            $payload['text'] = $textOptions;
        }

        if ($this->includeMarkdown) {
            $markdownOptions = true;

            if ($this->maxCharacters !== null) {
                $markdownOptions = ['maxCharacters' => $this->maxCharacters];
            }

            $payload['markdown'] = $markdownOptions;
        }

        if ($this->includeHighlights) {
            $highlightOptions = true;

            if ($this->highlightsPerUrl !== null || $this->highlightWords !== null) {
                $highlightOptions = array_filter([
                    'highlightsPerUrl' => $this->highlightsPerUrl,
                    'numSentences' => $this->highlightWords,
                ]);
            }

            $payload['highlights'] = $highlightOptions;
        }

        if ($this->includeSummary) {
            $summaryOptions = true;

            if ($this->summaryQuery !== null) {
                $summaryOptions = ['query' => $this->summaryQuery];
            }

            $payload['summary'] = $summaryOptions;
        }

        if ($this->livecrawl !== null) {
            $payload['livecrawl'] = $this->livecrawl->value;

            if ($this->livecrawlTimeout !== null) {
                $payload['livecrawlTimeout'] = $this->livecrawlTimeout;
            }
        }

        if ($this->includeSubpages) {
            $payload['subpages'] = $this->subpageLimit !== null
                ? $this->subpageLimit
                : true;
        }

        return $payload;
    }

    /**
     * Execute the contents request.
     */
    public function get(): ContentsResponse
    {
        return $this->client->executeContents($this);
    }

    /**
     * Execute and return first content.
     */
    public function first(): ?\Sepehr_Mohseni\Exaravel\DTOs\PageContent
    {
        return $this->get()->first();
    }
}
