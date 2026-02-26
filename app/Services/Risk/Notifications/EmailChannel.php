<?php

namespace App\Services\Risk\Notifications;

use App\Notifications\RiskAlertNotification;
use Illuminate\Support\Facades\Notification;

class EmailChannel implements NotificationChannelInterface
{
    public function send(array $payload): void
    {
        $recipients = array_filter(
            config('risk_notifications.email.recipients', [])
        );

        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $recipient) {
            Notification::route('mail', $recipient)
                ->notify(new RiskAlertNotification($payload));
        }
    }
}
