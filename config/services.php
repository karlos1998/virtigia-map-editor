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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'virtigia_page' => [
        'client_id' => env('VIRTIGIA_PAGE_CLIENT_ID'),
        'client_secret' => env('VIRTIGIA_PAGE_CLIENT_SECRET'),
        'redirect' => env('VIRTIGIA_PAGE_REDIRECT'),
        'url' => env('VIRTIGIA_PAGE_URL'), // Dodaj URL aplikacji A
    ],

    'laravelpassport' => [
        'client_id' => env('LARAVELPASSPORT_CLIENT_ID'),
        'client_secret' => env('LARAVELPASSPORT_CLIENT_SECRET'),
        'redirect' => env('LARAVELPASSPORT_REDIRECT_URI'),
        'host' => env('LARAVELPASSPORT_HOST'),
    ],

];
