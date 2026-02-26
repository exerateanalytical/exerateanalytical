<?php

namespace App\Services\Risk\Notifications;

use Illuminate\Support\Facades\Log;

class LogChannel implements NotificationChannelInterface
{
    public function send(array $payload): void
    {
        Log::info('RiskAlert notification', $payload);
    }
}
