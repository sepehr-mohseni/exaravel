<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\DTOs;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Represents the response from an Exa.ai contents request.
 *
 * @implements IteratorAggregate<int, PageContent>
 */
final class ContentsResponse implements Countable, IteratorAggregate
{
    /** @var array<PageContent> */
    private array $contents;

    private ?string $requestId;

    /**
     * @param  array<PageContent>  $contents
     */
    public function __construct(
        array $contents,
        ?string $requestId = null,
    ) {
        $this->contents = $contents;
        $this->requestId = $requestId;
    }

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?string $requestId = null): self
    {
        $contents = array_map(
            fn (array $item): PageContent => PageContent::fromArray($item),
            $data['results'] ?? []
        );

        return new self(
            contents: $contents,
            requestId: $requestId,
        );
    }

    /**
     * Get the request ID.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get all contents.
     *
     * @return array<PageContent>
     */
    public function all(): array
    {
        return $this->contents;
    }

    /**
     * Get the first content.
     */
    public function first(): ?PageContent
    {
        return $this->contents[0] ?? null;
    }

    /**
     * Get content at specific index.
     */
    public function get(int $index): ?PageContent
    {
        return $this->contents[$index] ?? null;
    }

    /**
     * Check if contents are empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->contents);
    }

    /**
     * Check if contents are not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Count the number of contents.
     */
    public function count(): int
    {
        return count($this->contents);
    }

    /**
     * Get iterator for contents.
     *
     * @return Traversable<int, PageContent>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->contents);
    }

    /**
     * Map over contents.
     *
     * @template T
     *
     * @param  callable(PageContent): T  $callback
     * @return array<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->contents);
    }

    /**
     * Get all texts from contents.
     *
     * @return array<string|null>
     */
    public function texts(): array
    {
        return $this->map(fn (PageContent $content): ?string => $content->text);
    }

    /**
     * Get all markdowns from contents.
     *
     * @return array<string|null>
     */
    public function markdowns(): array
    {
        return $this->map(fn (PageContent $content): ?string => $content->markdown);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'results' => array_map(
                fn (PageContent $content): array => $content->toArray(),
                $this->contents
            ),
            'requestId' => $this->requestId,
        ];
    }
}
