<?php

return [
    'ranking_api_url' => env('RANKING_API_URL'),
    'ranking_api_key' => env('RANKING_API_KEY'),
    'ranking_provider_timeout' => 15,
    'ranking_max_position' => 100,
    'crawl_limit' => env('RANKWATCH_CRAWL_LIMIT', 8),
    'crawl' => [
        'max_pages' => env('RANKWATCH_CRAWL_LIMIT', 8),
        'max_links_per_page' => 40,
        'max_requests' => 40,
        'max_body_bytes' => 2 * 1024 * 1024,
        'timeout' => 12,
        'connect_timeout' => 5,
        'max_redirects' => 3,
        'lock_ttl' => 900,
        'job_timeout' => 600,
        'job_tries' => 2,
        'job_backoff' => 60,
        'allowed_ports' => [
            'http' => [80],
            'https' => [443],
        ],
    ],
    'ranking_job' => [
        'lock_ttl' => 300,
        'timeout' => 60,
        'tries' => 2,
        'backoff' => 60,
    ],
    'scoring' => [
        'critical' => 10,
        'high' => 5,
        'medium' => 3,
        'low' => 1,
    ],
];
