<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passcode Retry Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of automatic retry attempts for a failed smart-lock
    | passcode operation (provisioning or revocation) before it is marked
    | max_attempts_reached and requires manual intervention.
    |
    */

    'max_retry_attempts' => env('LOCKS_MAX_RETRY_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Retry Backoff
    |--------------------------------------------------------------------------
    |
    | Minutes to wait before the next automatic retry after a failure.
    |
    */

    'retry_backoff_minutes' => env('LOCKS_RETRY_BACKOFF_MINUTES', 20),

];
