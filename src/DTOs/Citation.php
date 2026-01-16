<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\DTOs;

/**
 * Represents a citation from an Exa.ai answer response.
 */
final readonly class Citation
{
    /**
     * @param  array<string>|null  $highlights
     */
    public function __construct(
        public string $url,
        public string $id,
        public ?string $title = null,
        public ?string $publishedDate = null,
        public ?string $author = null,
        public ?string $text = null,
        public ?array $highlights = null,
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
            publishedDate: $data['publishedDate'] ?? null,
            author: $data['author'] ?? null,
            text: $data['text'] ?? null,
            highlights: $data['highlights'] ?? null,
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
            'text' => $this->text,
            'highlights' => $this->highlights,
        ], fn ($value): bool => $value !== null);
    }
}
