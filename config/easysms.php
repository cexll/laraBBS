<?php
use Overtrue\EasySms\EasySms;
return [
    // HTTP 请求的超时时间(s)
    'timeout' => 10.0,

    // 默认发送配置
    'default' => [
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        'geteways' => [
            'aliyun'
        ],
    ],

    'gateways' => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'aliyun' => [
            'access_key_id' => env('SMS_ALIYUN_ACCESS_KEY_ID'),
            'access_key_secret' => env('SMS_ALIYUN_ACCESS_KEY_SECRET'),
            'sign_name' => 'larabbs',
            'templates' => [
                'register' => env('SMS_ALIYUN_TEMPLATE_REGISTER'),
            ]
        ],
    ],
];
