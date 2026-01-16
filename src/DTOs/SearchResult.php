<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents a single search result from Exa.ai.
 */
final readonly class SearchResult
{
    public string $url;

    public ?string $publishedDate;

    /**
     * @param  array<string>|null  $highlights
     * @param  array<float>|null  $highlightScores
     */
    public function __construct(
        string $url,
        public string $id,
        public ?string $title = null,
        ?string $publishedDate = null,
        public ?string $author = null,
        public ?float $score = null,
        public ?string $text = null,
        public ?array $highlights = null,
        public ?array $highlightScores = null,
        public ?string $summary = null,
        public ?string $image = null,
        public ?string $favicon = null,
    ) {
        // Validate URL
        if ($url !== '' && ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: {$url}");
        }
        $this->url = $url;

        // Format published date
        if ($publishedDate === null) {
            $this->publishedDate = null;
        } else {
            try {
                $date = new DateTimeImmutable($publishedDate);
                $this->publishedDate = $date->format('Y-m-d\TH:i:s\Z');
            } catch (\Exception) {
                $this->publishedDate = $publishedDate;
            }
        }
    }

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'] ?? '',
            id: $data['id'] ?? '',
            title: $data['title'] ?? null,
            publishedDate: $data['publishedDate'] ?? null,
            author: $data['author'] ?? null,
            score: isset($data['score']) ? (float) $data['score'] : null,
            text: $data['text'] ?? null,
            highlights: $data['highlights'] ?? null,
            highlightScores: $data['highlightScores'] ?? null,
            summary: $data['summary'] ?? null,
            image: $data['image'] ?? null,
            favicon: $data['favicon'] ?? null,
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'publishedDate' => $this->publishedDate,
            'author' => $this->author,
            'score' => $this->score,
            'text' => $this->text,
            'highlights' => $this->highlights,
            'highlightScores' => $this->highlightScores,
            'summary' => $this->summary,
            'image' => $this->image,
            'favicon' => $this->favicon,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * Check if content was retrieved.
     */
    public function hasContent(): bool
    {
        return $this->text !== null || $this->summary !== null || ! empty($this->highlights);
    }

    /**
     * Check if highlights are available.
     */
    public function hasHighlights(): bool
    {
        return ! empty($this->highlights);
    }

    /**
     * Get domain from URL.
     */
    public function getDomain(): ?string
    {
        $parsed = parse_url($this->url);

        return $parsed['host'] ?? null;
    }
}
