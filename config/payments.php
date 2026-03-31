<?php

return [
    'gateways' => [
        // 'tap' => [
        //     'value' => 'tap',
        //     'icon' => 'tap.jpg',
        //     'currency' => 'SAR',
        //     'explanation' => 'Pay with Tap',
        //     'class' => \App\Services\PaymentMethods\TapPayment::class,
        //     'api_key' => 'https://api.tap.company/v2/charges',
        //     'sandbox' => false,
        //     'sandbox_public_key' => 'pk_test_SLzph7oGYUXHBqFfjEriCgu8',
        //     'sandbox_secret_key' => 'sk_test_6jOFQ78hpfZ2DAmVinJBrE1w',
        //     'public_key' => 'pk_live_G6jacgyCBlvOZYt7mqIPkerh',
        //     'secret_key' =>  'sk_live_gGSqea2upmE8ylihwCzL1MVQ',
        //     'test_mode' => false,
        // ],


        'geidea' => [
            'public_key'   => env('GEIDEA_PUBLIC_KEY'),
            'api_password' => env('GEIDEA_API_PASSWORD'),
            'api_base'     => env('GEIDEA_API_BASE', 'https://api.ksamerchant.geidea.net'),
            'hpp_base'     => env('GEIDEA_HPP_BASE', 'https://www.ksamerchant.geidea.net/hpp/checkout'),
            'value' => 'geidea',
            'icon' => 'tap.jpg',
            'currency' => 'SAR',
            'explanation' => 'Pay with Tap',
            'title' => 'الدفع بالبطاقة',
        ],


    ],

    'default' => 'tap',
];
