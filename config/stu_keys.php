<?php

return [
    /*
    |--------------------------------------------------------------------------
    | STU Key Management Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the STU Key Management System
    |
    */

    'hr_sync' => [
        'enabled' => env('FEATURE_HR_SYNC', false),
        'base_url' => env('HR_API_BASE'),
        'api_key' => env('HR_API_KEY'),
        'sync_cron' => env('HR_API_SYNC_CRON', '*/30 * * * *'),
    ],

    'notifications' => [
        'sms_provider' => env('SMS_PROVIDER', 'hubtel'),
        'whatsapp_provider' => env('WHATSAPP_PROVIDER', 'none'),
        
        'hubtel' => [
            'api_key' => env('HUBTEL_API_KEY'),
            'client_id' => env('HUBTEL_CLIENT_ID'),
            'client_secret' => env('HUBTEL_CLIENT_SECRET'),
            'sender_id' => env('HUBTEL_SENDER_ID', 'STU-Keys'),
        ],
    ],

    'pwa' => [
        'offline_tolerance_hours' => env('PWA_OFFLINE_TOLERANCE_HOURS', 4),
        'background_sync_interval' => env('PWA_BACKGROUND_SYNC_INTERVAL', 5),
    ],

    'features' => [
        'otp_verification' => env('FEATURE_OTP_VERIFICATION', false),
        'shift_rosters' => env('FEATURE_SHIFT_ROSTERS', false),
        'api_integration' => env('FEATURE_API_INTEGRATION', true),
    ],

    'security' => [
        'require_signature_checkout' => true,
        'require_signature_checkin' => false,
        'photo_optional' => true,
        'max_photo_size' => 2048, // KB
        'auto_logout_minutes' => 120,
    ],

    'reports' => [
        'retention_days' => 365,
        'export_limit' => 10000,
    ],
];
