<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\DTOs;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Represents the response from an Exa.ai search request.
 *
 * @implements IteratorAggregate<int, SearchResult>
 */
final class ExaResponse implements Countable, IteratorAggregate
{
    /** @var array<SearchResult> */
    private array $results;

    private ?string $autopromptString;

    private ?string $requestId;

    private ?int $resolvedSearchType;

    /**
     * @param  array<SearchResult>  $results
     */
    public function __construct(
        array $results,
        ?string $autopromptString = null,
        ?string $requestId = null,
        ?int $resolvedSearchType = null,
    ) {
        $this->results = $results;
        $this->autopromptString = $autopromptString !== null ? trim($autopromptString) : null;
        $this->requestId = $requestId;
        $this->resolvedSearchType = $resolvedSearchType;
    }

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?string $requestId = null): self
    {
        $results = array_map(
            fn (array $item): SearchResult => SearchResult::fromArray($item),
            $data['results'] ?? []
        );

        $resolvedSearchType = $data['resolvedSearchType'] ?? null;
        if ($resolvedSearchType !== null && ! is_int($resolvedSearchType)) {
            $resolvedSearchType = is_numeric($resolvedSearchType) ? (int) $resolvedSearchType : null;
        }

        return new self(
            results: $results,
            autopromptString: $data['autopromptString'] ?? null,
            requestId: $requestId,
            resolvedSearchType: $resolvedSearchType,
        );
    }

    /**
     * Get the autoprompt string.
     */
    public function getAutopromptString(): ?string
    {
        return $this->autopromptString;
    }

    /**
     * Get the request ID.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get the resolved search type.
     */
    public function getResolvedSearchType(): ?int
    {
        return $this->resolvedSearchType;
    }

    /**
     * Get all results.
     *
     * @return array<SearchResult>
     */
    public function all(): array
    {
        return $this->results;
    }

    /**
     * Get the first result.
     */
    public function first(): ?SearchResult
    {
        return $this->results[0] ?? null;
    }

    /**
     * Get the last result.
     */
    public function last(): ?SearchResult
    {
        if (empty($this->results)) {
            return null;
        }

        return $this->results[array_key_last($this->results)];
    }

    /**
     * Get result at specific index.
     */
    public function get(int $index): ?SearchResult
    {
        return $this->results[$index] ?? null;
    }

    /**
     * Check if results are empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->results);
    }

    /**
     * Check if results are not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Count the number of results.
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * Get iterator for results.
     *
     * @return Traversable<int, SearchResult>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    /**
     * Map over results.
     *
     * @template T
     *
     * @param  callable(SearchResult): T  $callback
     * @return array<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->results);
    }

    /**
     * Filter results.
     *
     * @param  callable(SearchResult): bool  $callback
     * @return array<SearchResult>
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->results, $callback);
    }

    /**
     * Get all URLs from results.
     *
     * @return array<string>
     */
    public function urls(): array
    {
        return $this->map(fn (SearchResult $result): string => $result->url);
    }

    /**
     * Get all titles from results.
     *
     * @return array<string|null>
     */
    public function titles(): array
    {
        return $this->map(fn (SearchResult $result): ?string => $result->title);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'results' => array_map(fn (SearchResult $result): array => $result->toArray(), $this->results),
            'autopromptString' => $this->autopromptString,
            'requestId' => $this->requestId,
            'resolvedSearchType' => $this->resolvedSearchType,
        ];
    }
}
