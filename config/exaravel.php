<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Exa.ai API Key
    |--------------------------------------------------------------------------
    |
    | Your Exa.ai API key. You can obtain one from https://exa.ai
    |
    */
    'api_key' => env('EXA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Exa.ai API.
    |
    */
    'base_url' => env('EXA_BASE_URL', 'https://api.exa.ai'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The request timeout in seconds.
    |
    */
    'timeout' => env('EXA_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Connect Timeout
    |--------------------------------------------------------------------------
    |
    | The connection timeout in seconds.
    |
    */
    'connect_timeout' => env('EXA_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic retries with exponential backoff.
    |
    */
    'retry' => [
        'times' => env('EXA_RETRY_TIMES', 3),
        'sleep_milliseconds' => env('EXA_RETRY_SLEEP', 500),
        'when' => [429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Search Type
    |--------------------------------------------------------------------------
    |
    | The default search type: 'auto', 'neural', or 'keyword'.
    |
    */
    'default_search_type' => env('EXA_DEFAULT_SEARCH_TYPE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Default Number of Results
    |--------------------------------------------------------------------------
    |
    | The default number of results to return.
    |
    */
    'default_num_results' => env('EXA_DEFAULT_NUM_RESULTS', 10),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable request/response logging.
    |
    */
    'logging' => [
        'enabled' => env('EXA_LOGGING_ENABLED', false),
        'channel' => env('EXA_LOGGING_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multiple Connections
    |--------------------------------------------------------------------------
    |
    | Define multiple API connections with different configurations.
    |
    */
    'connections' => [
        'default' => [
            'api_key' => env('EXA_API_KEY'),
            'base_url' => env('EXA_BASE_URL', 'https://api.exa.ai'),
        ],
    ],
];
