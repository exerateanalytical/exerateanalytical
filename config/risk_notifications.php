<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Notification Channels
    |--------------------------------------------------------------------------
    | List the channel class names to enable. Channels are resolved via the
    | service container, so any constructor dependencies are auto-injected.
    |
    | Available:
    |   \App\Services\Risk\Notifications\LogChannel::class
    |   \App\Services\Risk\Notifications\WebhookChannel::class
    |   \App\Services\Risk\Notifications\EmailChannel::class
    */
    'channels' => array_filter([
        \App\Services\Risk\Notifications\LogChannel::class,
        env('RISK_NOTIFICATION_WEBHOOK_URL') ? \App\Services\Risk\Notifications\WebhookChannel::class : null,
        env('RISK_NOTIFICATION_EMAIL_RECIPIENTS') ? \App\Services\Risk\Notifications\EmailChannel::class : null,
    ]),

    /*
    |--------------------------------------------------------------------------
    | Retriggered-Event Suppression Window
    |--------------------------------------------------------------------------
    | Suppresses duplicate notifications for retriggered events on the same
    | alert within this many minutes.
    */
    'suppression_minutes' => (int) env('RISK_NOTIFICATION_SUPPRESSION_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Webhook Channel
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'url'     => env('RISK_NOTIFICATION_WEBHOOK_URL', ''),
        'timeout' => (int) env('RISK_NOTIFICATION_WEBHOOK_TIMEOUT', 5),
        'secret'  => env('RISK_NOTIFICATION_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Channel
    |--------------------------------------------------------------------------
    | RISK_NOTIFICATION_EMAIL_RECIPIENTS accepts a comma-separated list of
    | addresses: "ops@example.com,ciso@example.com"
    */
    'email' => [
        'recipients' => array_filter(
            explode(',', env('RISK_NOTIFICATION_EMAIL_RECIPIENTS', ''))
        ),
    ],

];
