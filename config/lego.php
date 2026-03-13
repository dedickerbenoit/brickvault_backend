<?php

return [
    'graphql' => [
        'rate_limit_ms' => env('LEGO_GRAPHQL_RATE_LIMIT_MS', 200),
    ],

    'scraping' => [
        'base_url' => env('LEGO_SCRAPING_BASE_URL', 'https://www.lego.com'),
        'rate_limit_ms' => env('LEGO_SCRAPING_RATE_LIMIT_MS', 500),
    ],
];
