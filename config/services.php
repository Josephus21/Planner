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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gsuite' => [
    'base_url'   => env('GSUITE_API_BASE_URL', 'http://gsuite.graphicstar.com.ph/api'),
    'token'      => env('GSUITE_API_TOKEN'),
    'employee_pk'=> env('GSUITE_EMPLOYEE_PK'),
    'prepared_by'=> env('GSUITE_PREPARED_BY', 'System'),
    'timeout'    => (int) env('GSUITE_TIMEOUT', 30),

    'allowed_syspk_job' => [
        '41342b80-2ed2-11eb-b09a-b3cd80c3d9e0',
        '3ee3b470-79d4-11eb-abaf-399f73b215fe',
    ],
],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
