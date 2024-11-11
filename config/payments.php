<?php

return [
    'gateways' => [
        'tap' => [
            'value' => 'tap',
            'icon' => 'tap-payments-logo.png',
            'currency' => 'SAR',
            'explanation' => 'Pay with Tap',
            'class' => \App\Services\PaymentMethods\TapPayment::class,
            'api_key' => 'https://api.tap.company/v2/charges',
            'sandbox' => true,
            'sandbox_public_key' =>'pk_test_ECejbPF6GVpZQy0vnU98JzYt',
            'sandbox_secret_key' => 'sk_test_X9zEfo35C0w1gJZl48qVU6dj',
            'public_key' => env('TAP_PAYMENT_PUBLIC_KEY'),
            'secret_key' => env('TAP_PAYMENT_SECRET_KEY'),
            'test_mode' => true,
        ],


    ],

    'default' => 'tap',
];
