<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiskAlertNotification extends Notification
{
    public function __construct(private readonly array $payload) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = sprintf(
            '[%s] Risk Alert: %s (%s)',
            strtoupper($this->payload['severity']),
            $this->payload['alert_type'],
            $this->payload['event_type'],
        );

        return (new MailMessage)
            ->subject($subject)
            ->line("Country: {$this->payload['country_id']}")
            ->line("Alert type: {$this->payload['alert_type']}")
            ->line("Event: {$this->payload['event_type']}")
            ->line("Severity: {$this->payload['severity']}")
            ->line("Occurred at: {$this->payload['occurred_at']}");
    }
}
