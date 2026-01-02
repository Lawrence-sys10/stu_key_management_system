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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IRMTS API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to IRMTS unified API which aggregates
    | data from school management system and PTM system.
    |
    */
    'irmts_api' => [
        'base_url' => env('IRMTS_API_URL', 'http://localhost:8002'),
        'key' => env('IRMTS_API_SECRET', ''),
    ],

    'gekychat' => [
        // Platform API is on api subdomain, not chat subdomain
        // Routes are at: api.gekychat.test/platform/oauth/token
        // So base_url should be just the domain (no /api prefix)
        'base_url' => env('GEKYCHAT_API_URL', env('APP_ENV') === 'local' ? 'http://api.gekychat.test' : 'https://api.gekychat.com'),
        'client_id' => env('GEKYCHAT_CLIENT_ID'),
        'client_secret' => env('GEKYCHAT_CLIENT_SECRET'),
        'system_bot_user_id' => (int) env('GEKYCHAT_SYSTEM_BOT_USER_ID', 0),
    ],

];
