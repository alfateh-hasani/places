<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP format
    |--------------------------------------------------------------------------
    |
    | Can be one of alpha, alphanumeric, numeric
    |
    */
    'format' => env('OTP_FORMAT', 'numeric'),

    /*
    |--------------------------------------------------------------------------
    | OTP characters length
    |--------------------------------------------------------------------------
    |
    | Number of characters of OTP
    |
    */
    'length' => env('OTP_LENGTH', 4),

    /*
    |--------------------------------------------------------------------------
    | OTP expiration
    |--------------------------------------------------------------------------
    |
    | Number of minutes before OTP expires
    |
    */
    'expires' => env('OTP_EXPIRES', 15),

    /*
    |--------------------------------------------------------------------------
    | OTP notification
    |--------------------------------------------------------------------------
    |
    | Notification to use for OTP
    |
    */
    'notification' => \App\Notifications\OtpNotification::class,

    /*
    |--------------------------------------------------------------------------
    | OTP request cooldown
    |--------------------------------------------------------------------------
    |
    | Number of seconds a phone number must wait between two OTP requests.
    | This is enforced server-side to prevent SMS spamming / abuse.
    |
    */
    'request_cooldown' => env('OTP_REQUEST_COOLDOWN', 90),

    /*
    |--------------------------------------------------------------------------
    | OTP send quota + lockout
    |--------------------------------------------------------------------------
    |
    | A phone may only be sent "max_attempts" codes within "attempts_window"
    | seconds. Exceeding that quota locks the phone out of new OTP requests for
    | "block_duration" seconds. Staff can lift the lock manually (customer
    | support). All values are seconds and safe to tune per-environment.
    |
    */
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
    'attempts_window' => env('OTP_ATTEMPTS_WINDOW', 600),
    'block_duration' => env('OTP_BLOCK_DURATION', 86400),
];
