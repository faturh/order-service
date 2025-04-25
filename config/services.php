<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // URL layanan UserService
    'user_service' => [
        'url' => env('USER_SERVICE_URL', 'http://user-service.test'),
    ],
    
    // URL layanan ProductService
    'product_service' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://product-service.test'),
    ],
];