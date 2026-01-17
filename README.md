# Exaravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sepehr-mohseni/exaravel.svg?style=flat-square)](https://packagist.org/packages/sepehr-mohseni/exaravel)
[![Tests](https://img.shields.io/github/actions/workflow/status/sepehr-mohseni/exaravel/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/sepehr-mohseni/exaravel/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![License](https://img.shields.io/packagist/l/sepehr-mohseni/exaravel.svg?style=flat-square)](https://packagist.org/packages/sepehr-mohseni/exaravel)

A high-performance, strictly-typed, and enterprise-grade Laravel wrapper for the [Exa.ai](https://exa.ai) v2.1 API.

## Features

- 🚀 **PHP 8.2+** with Asymmetric Visibility and Property Hooks (PHP 8.4)
- 🔧 **Fluent Builder Pattern** for maximum IDE discoverability
- 🛡️ **Strictly Typed DTOs** - No raw arrays reach the end-user
- 🔄 **Automatic Retries** with exponential backoff for 429 and 5xx errors
- 📊 **Laravel Context Integration** for enterprise observability
- 🔑 **Multi-Connection Support** for managing multiple API keys
- ✅ **PHPStan Level 9** compliant

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x

## Installation

```bash
composer require sepehr-mohseni/exaravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=exaravel-config
```

Add your API key to your `.env` file:

```env
EXA_API_KEY=your-api-key-here
```

## Usage

### Search

```php
use Sepehr_Mohseni\Exaravel\Facades\Exa;
use Sepehr_Mohseni\Exaravel\Enums\SearchType;
use Sepehr_Mohseni\Exaravel\Enums\Category;

// Basic search
$results = Exa::search('Laravel best practices')->get();

// Advanced search with fluent builder
$results = Exa::search('Machine learning frameworks')
    ->type(SearchType::Neural)
    ->category(Category::GitHub)
    ->numResults(20)
    ->includeDomains(['github.com', 'gitlab.com'])
    ->excludeDomains(['stackoverflow.com'])
    ->startPublishedDate('2024-01-01')
    ->endPublishedDate(new DateTime('now'))
    ->withContents(text: true, highlights: true)
    ->withAutoprompt()
    ->get();

// Iterate over results
foreach ($results as $result) {
    echo $result->title . ' - ' . $result->url . PHP_EOL;
}

// Get first result directly
$firstResult = Exa::search('PHP 8.4 features')->first();
```

### Find Similar

```php
// Find content similar to a URL
$results = Exa::findSimilar('https://laravel.com/docs/eloquent')
    ->numResults(10)
    ->excludeSourceDomain()
    ->category(Category::ResearchPaper)
    ->withContents(text: true, summary: true)
    ->get();
```

### Get Contents

```php
// Retrieve full content for specific document IDs
$contents = Exa::contents(['doc-id-1', 'doc-id-2', 'doc-id-3'])
    ->withText(maxCharacters: 10000)
    ->withMarkdown()
    ->withHighlights(perUrl: 3)
    ->withSummary('What are the key points?')
    ->get();

foreach ($contents as $content) {
    echo $content->title . PHP_EOL;
    echo $content->getPreferredContent(); // Returns markdown if available, otherwise text
}
```

### Answer (Agentic Endpoint)

```php
// Get a direct answer with citations
$response = Exa::answer('What are the new features in PHP 8.4?')
    ->numResults(5)
    ->neural()
    ->includeDomains(['php.net', 'wiki.php.net'])
    ->systemPrompt('You are a PHP expert. Provide detailed technical explanations.')
    ->get();

echo $response->answer;

foreach ($response->citations as $citation) {
    echo "Source: {$citation->url}" . PHP_EOL;
}

// Get just the answer text
$answerText = Exa::answer('Explain Laravel service providers')->text();
```

### Working with Results

```php
$results = Exa::search('Laravel packages')->get();

// Collection-like methods
$urls = $results->urls();
$titles = $results->titles();

// Filter results
$highScoring = $results->filter(fn ($result) => $result->score > 0.8);

// Map results
$summaries = $results->map(fn ($result) => [
    'title' => $result->title,
    'url' => $result->url,
]);

// Check results
if ($results->isEmpty()) {
    echo 'No results found';
}

echo "Found {$results->count()} results";
```

### Multiple Connections

Configure multiple API keys in `config/exaravel.php`:

```php
'connections' => [
    'default' => [
        'api_key' => env('EXA_API_KEY'),
    ],
    'premium' => [
        'api_key' => env('EXA_PREMIUM_API_KEY'),
        'timeout' => 60,
    ],
],
```

Use a specific connection:

```php
$results = Exa::using('premium')
    ->search('Complex query')
    ->get();

// Or use a custom API key on the fly
$results = Exa::withApiKey('custom-key')
    ->search('Query')
    ->get();
```

### Error Handling

```php
use Sepehr_Mohseni\Exaravel\Exceptions\AuthenticationException;
use Sepehr_Mohseni\Exaravel\Exceptions\RateLimitException;
use Sepehr_Mohseni\Exaravel\Exceptions\InsufficientCreditsException;
use Sepehr_Mohseni\Exaravel\Exceptions\ValidationException;

try {
    $results = Exa::search('query')->get();
} catch (AuthenticationException $e) {
    // Invalid API key
} catch (RateLimitException $e) {
    // Rate limit exceeded
    $retryAfter = $e->retryAfter; // seconds to wait
} catch (InsufficientCreditsException $e) {
    // Need more API credits
} catch (ValidationException $e) {
    // Invalid request parameters
    $errors = $e->errors;
}
```

### Livecrawl Mode

```php
use Sepehr_Mohseni\Exaravel\Enums\LivecrawlMode;

$results = Exa::search('Latest tech news')
    ->livecrawl(LivecrawlMode::Always, timeout: 10000)
    ->get();
```

## Configuration

```php
// config/exaravel.php

return [
    'api_key' => env('EXA_API_KEY'),
    'base_url' => env('EXA_BASE_URL', 'https://api.exa.ai'),
    'timeout' => env('EXA_TIMEOUT', 30),
    'connect_timeout' => env('EXA_CONNECT_TIMEOUT', 10),
    
    'retry' => [
        'times' => env('EXA_RETRY_TIMES', 3),
        'sleep_milliseconds' => env('EXA_RETRY_SLEEP', 500),
        'when' => [429, 500, 502, 503, 504],
    ],
    
    'default_search_type' => env('EXA_DEFAULT_SEARCH_TYPE', 'auto'),
    'default_num_results' => env('EXA_DEFAULT_NUM_RESULTS', 10),
    
    'logging' => [
        'enabled' => env('EXA_LOGGING_ENABLED', false),
        'channel' => env('EXA_LOGGING_CHANNEL', 'stack'),
    ],
];
```

## Testing

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Sepehr Mohseni](https://github.com/sepehr-mohseni)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
