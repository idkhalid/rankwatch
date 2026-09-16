<?php

return [
    'free' => [
        'projects' => 1,
        'keywords_per_project' => 10,
        'crawl_pages' => 10,
        'crawl_frequency' => 'weekly',
        'ranking_frequency' => 'weekly',
        'history_days' => 30,
        'features' => [
            'keyword_drop_notifications' => false,
            'crawl_completed_notifications' => false,
            'critical_issue_notifications' => true,
            'historical_comparison' => false,
            'pdf_export' => false,
        ],
    ],
    'pro' => [
        'projects' => 10,
        'keywords_per_project' => 100,
        'crawl_pages' => 100,
        'crawl_frequency' => 'daily',
        'ranking_frequency' => 'daily',
        'history_days' => 365,
        'features' => [
            'keyword_drop_notifications' => true,
            'crawl_completed_notifications' => true,
            'critical_issue_notifications' => true,
            'historical_comparison' => false,
            'pdf_export' => false,
        ],
    ],
];
