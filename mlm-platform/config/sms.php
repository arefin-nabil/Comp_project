<?php

return [
    'default' => env('SMS_DRIVER', 'log'),

    'drivers' => [
        'ssl_wireless' => [
            'api_token' => env('SSL_WIRELESS_API_TOKEN'),
            'sid' => env('SSL_WIRELESS_SID'),
            'csms_id' => env('SSL_WIRELESS_CSMS_ID'),
            'url' => env('SSL_WIRELESS_URL', 'https://smsplus.sslwireless.com/api/v3/send-sms'),
        ],
        
        'log' => [
            // Uses Laravel's Log facade
        ],
    ],
];
