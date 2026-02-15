<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OwnerRez API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OwnerRez API integration including authentication,
    | webhook settings, availability checks, and sync options.
    |
    */

    'api_url' => env('OWNERREZ_API_URL', 'https://api.ownerrez.com'),

    // Basic Auth credentials for API
    'username' => env('OWNERREZ_USERNAME'),
    'password' => env('OWNERREZ_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'user' => env('OWNERREZ_WEBHOOK_USERNAME'),
        'password' => env('OWNERREZ_WEBHOOK_PASSWORD'),
        'cache_ttl' => (int) env('OWNERREZ_WEBHOOK_CACHE_TTL', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Availability Check Settings
    |--------------------------------------------------------------------------
    */

    'availability' => [
        'enabled' => env('OWNERREZ_AVAILABILITY_CHECK_ENABLED', true),
        'cache_ttl' => (int) env('OWNERREZ_AVAILABILITY_CACHE_TTL', 300), // 5 minutes
        'strict_mode' => env('OWNERREZ_STRICT_MODE', false), // رفض الحجز عند فشل API
        'timeout' => (int) env('OWNERREZ_API_TIMEOUT', 10), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'auto_sync_outbound' => env('OWNERREZ_AUTO_SYNC_OUTBOUND', true),
        'auto_sync_inbound' => env('OWNERREZ_AUTO_SYNC_INBOUND', true),
        'sync_guest_data' => env('OWNERREZ_SYNC_GUEST_DATA', true),
        'custom_field_definition_id' => (int) env('OWNERREZ_CUSTOM_FIELD_DEFINITION_ID', 294966319),
        'custom_field_value' => env('OWNERREZ_CUSTOM_FIELD_VALUE', 'places'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'max_requests_per_minute' => 60,
        'retry_after_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'log_api_calls' => env('OWNERREZ_LOG_API_CALLS', true),

    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    */

    'oauth' => [
        'client_id' => env('OWNERREZ_OAUTH_CLIENT_ID'),
        'client_secret' => env('OWNERREZ_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('OWNERREZ_OAUTH_REDIRECT_URI', env('APP_URL').'/api/ownerrez/oauth/callback'),
        'access_token' => env('OWNERREZ_ACCESS_TOKEN'),
        'user_id' => env('OWNERREZ_USER_ID'),
    ],

];
