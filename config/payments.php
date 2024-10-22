<?php

return [
    'gateways' => [
        'tap' => [
            'value' => 'tap',
            'icon' => 'fa-cc-visa',
            'currency' => 'SAR',
            'class' => \App\Services\PaymentMethods\TapPayment::class,
            'api_key' => env('TAP_API_KEY'),
            'sandbox' => env('TAP_PAYMENT_MODE', true),
            'sandbox_public_key' => env('TAP_PAYMENT_TEST_PUBLIC_KEY'),
            'sandbox_secret_key' => env('TAP_PAYMENT_TEST_SECRET_KEY'),
            'public_key' => env('TAP_PAYMENT_PUBLIC_KEY'),
            'secret_key' => env('TAP_PAYMENT_SECRET_KEY'),
            'test_mode' => env('TAP_PAYMENT_TEST_MODE', true),
        ],


    ],

    'default' => 'tap',
];
