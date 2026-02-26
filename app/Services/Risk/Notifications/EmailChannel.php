<?php

namespace App\Services\Risk\Notifications;

use App\Notifications\RiskAlertNotification;
use Illuminate\Support\Facades\Notification;

class EmailChannel implements NotificationChannelInterface
{
    public function __construct(private readonly array $channelConfig = []) {}

    public function send(array $payload): void
    {
        $recipients = array_filter($this->channelConfig['recipients'] ?? []);

        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $recipient) {
            Notification::route('mail', $recipient)
                ->notify(new RiskAlertNotification($payload));
        }
    }
}
