<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\DTOs;

/**
 * Represents page content retrieved from Exa.ai contents endpoint.
 */
final readonly class PageContent
{
    /**
     * @param  array<string>|null  $highlights
     */
    public function __construct(
        public string $url,
        public string $id,
        public ?string $title = null,
        public ?string $text = null,
        public ?string $markdown = null,
        public ?array $highlights = null,
        public ?string $summary = null,
    ) {}

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
            text: $data['text'] ?? null,
            markdown: $data['markdown'] ?? null,
            highlights: $data['highlights'] ?? null,
            summary: $data['summary'] ?? null,
        );
    }

    /**
     * Check if text content is available.
     */
    public function hasText(): bool
    {
        return $this->text !== null;
    }

    /**
     * Check if markdown content is available.
     */
    public function hasMarkdown(): bool
    {
        return $this->markdown !== null;
    }

    /**
     * Check if highlights are available.
     */
    public function hasHighlights(): bool
    {
        return ! empty($this->highlights);
    }

    /**
     * Get preferred content (markdown first, then text).
     */
    public function getPreferredContent(): ?string
    {
        return $this->markdown ?? $this->text;
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
            'text' => $this->text,
            'markdown' => $this->markdown,
            'highlights' => $this->highlights,
            'summary' => $this->summary,
        ], fn ($value): bool => $value !== null);
    }
}
