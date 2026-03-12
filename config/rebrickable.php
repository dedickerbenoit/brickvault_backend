<?php

return [

    'api_key' => env('REBRICKABLE_API_KEY'),

    'api_url' => env('REBRICKABLE_API_URL', 'https://rebrickable.com/api/v3'),

    'cache' => [
        'enabled' => env('REBRICKABLE_CACHE_ENABLED', true),
        'ttl' => env('REBRICKABLE_CACHE_TTL', 86400),
    ],

];
