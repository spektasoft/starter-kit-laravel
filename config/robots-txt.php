<?php

return [
    'environments' => [
        'production' => [
            'paths' => [
                '*' => [
                    'disallow' => [
                        '/admin',
                    ],
                ],
            ],
            'sitemaps' => [
                'sitemap.xml',
            ],
        ],
        'staging' => [
            'paths' => [
                '*' => [
                    'disallow' => [
                        '/',
                    ],
                ],
            ],
        ],
    ],
];
