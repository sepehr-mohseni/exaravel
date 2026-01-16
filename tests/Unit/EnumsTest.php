<?php

declare(strict_types=1);

use Sepehr_Mohseni\Exaravel\Enums\Category;
use Sepehr_Mohseni\Exaravel\Enums\LivecrawlMode;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;

describe('SearchType Enum', function (): void {
    it('has correct values', function (): void {
        expect(SearchType::Auto->value)->toBe('auto')
            ->and(SearchType::Neural->value)->toBe('neural')
            ->and(SearchType::Keyword->value)->toBe('keyword');
    });

    it('provides default', function (): void {
        expect(SearchType::default())->toBe(SearchType::Auto);
    });

    it('creates from string with fallback', function (): void {
        expect(SearchType::fromString('neural'))->toBe(SearchType::Neural)
            ->and(SearchType::fromString('invalid'))->toBe(SearchType::Auto);
    });
});

describe('Category Enum', function (): void {
    it('has correct values', function (): void {
        expect(Category::Company->value)->toBe('company')
            ->and(Category::ResearchPaper->value)->toBe('research paper')
            ->and(Category::News->value)->toBe('news')
            ->and(Category::GitHub->value)->toBe('github')
            ->and(Category::Tweet->value)->toBe('tweet')
            ->and(Category::PDF->value)->toBe('pdf');
    });

    it('provides all values', function (): void {
        $values = Category::values();

        expect($values)->toContain('company')
            ->and($values)->toContain('news')
            ->and($values)->toContain('github')
            ->and($values)->toHaveCount(count(Category::cases()));
    });
});

describe('LivecrawlMode Enum', function (): void {
    it('has correct values', function (): void {
        expect(LivecrawlMode::Always->value)->toBe('always')
            ->and(LivecrawlMode::Fallback->value)->toBe('fallback')
            ->and(LivecrawlMode::Never->value)->toBe('never');
    });

    it('provides default', function (): void {
        expect(LivecrawlMode::default())->toBe(LivecrawlMode::Fallback);
    });
});
