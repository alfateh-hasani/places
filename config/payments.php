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
            'sandbox_public_key' =>'pk_test_SLzph7oGYUXHBqFfjEriCgu8',
            'sandbox_secret_key' => 'sk_test_6jOFQ78hpfZ2DAmVinJBrE1w',
            'public_key' => 'pk_live_G6jacgyCBlvOZYt7mqIPkerh',
            'secret_key' =>  'sk_live_gGSqea2upmE8ylihwCzL1MVQ',
            'test_mode' => true,
        ],


    ],

    'default' => 'tap',
];
