<?php

declare(strict_types=1);

namespace Sepehr_Mohseni\Exaravel\DTOs;

/**
 * Represents the response from an Exa.ai answer request.
 */
final readonly class AnswerResponse
{
    /**
     * @param  array<Citation>  $citations
     */
    public function __construct(
        public string $answer,
        public array $citations = [],
        public ?string $requestId = null,
    ) {}

    /**
     * Create from API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?string $requestId = null): self
    {
        $citations = array_map(
            fn (array $item): Citation => Citation::fromArray($item),
            $data['citations'] ?? []
        );

        return new self(
            answer: $data['answer'] ?? '',
            citations: $citations,
            requestId: $requestId,
        );
    }

    /**
     * Get the answer text.
     */
    public function getAnswer(): string
    {
        return $this->answer;
    }

    /**
     * Check if the response has citations.
     */
    public function hasCitations(): bool
    {
        return ! empty($this->citations);
    }

    /**
     * Get the number of citations.
     */
    public function citationCount(): int
    {
        return count($this->citations);
    }

    /**
     * Get citation URLs.
     *
     * @return array<string>
     */
    public function citationUrls(): array
    {
        return array_map(fn (Citation $citation): string => $citation->url, $this->citations);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'citations' => array_map(
                fn (Citation $citation): array => $citation->toArray(),
                $this->citations
            ),
            'requestId' => $this->requestId,
        ];
    }
}
