<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ownerrez' => [
        'username' => env('OWNERREZ_USERNAME'),
        'password' => env('OWNERREZ_PASSWORD'),
        'webhook_user' => env('OWNERREZ_WEBHOOK_USERNAME'),
        'webhook_password' => env('OWNERREZ_WEBHOOK_PASSWORD'),
        'api_url' => env('OWNERREZ_API_URL', 'https://api.ownerrez.com'),
        'cache_ttl' => (int) env('OWNERREZ_WEBHOOK_CACHE_TTL', 180),
        'cache_key' => 'ownerrez:webhook:latest',
    ],

    'sciener' => [
        'client_id' => '2c959287a856409ca8b572090a4ba040',
        'client_secret' => 'dfa43b3b37160a8ef3042121c66a0dd4',
        'username' => 'abdoshahen2013@gmail.com',
        'password' => 'AZay2025',
    ],

    'guesty' => [
        'client_id' => env('GUESTY_CLIENT_ID'),
        'client_secret' => env('GUESTY_CLIENT_SECRET'),
        'base_url' => env('GUESTY_BASE_URL', 'https://open-api.guesty.com'),
        'token_url' => env('GUESTY_TOKEN_URL', 'https://open-api.guesty.com/oauth2/token'),
        'timeout' => (int) env('GUESTY_TIMEOUT', 10),
        'token_cache_key' => env('GUESTY_TOKEN_CACHE_KEY', 'guesty:access-token'),
    ],

];
