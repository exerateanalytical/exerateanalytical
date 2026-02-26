<?php

namespace App\Services\Risk;

use App\Models\RiskAlertEvent;
use App\Services\Risk\Notifications\NotificationChannelInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RiskNotificationService
{
    /** @var NotificationChannelInterface[] */
    private readonly array $channels;

    public function __construct()
    {
        $this->channels = collect(config('risk_notifications.channels', []))
            ->map(fn (string $class) => app($class))
            ->all();
    }

    public function notify(RiskAlertEvent $event): void
    {
        if ($this->isSuppressed($event)) {
            return;
        }

        $payload = $this->buildPayload($event);

        foreach ($this->channels as $channel) {
            try {
                $channel->send($payload);
            } catch (\Throwable $e) {
                Log::error('Risk notification channel failure', [
                    'channel'  => get_class($channel),
                    'event_id' => $event->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->markNotified($event);
    }

    private function buildPayload(RiskAlertEvent $event): array
    {
        return [
            'event_id'    => $event->id,
            'alert_id'    => $event->risk_alert_id,
            'country_id'  => $event->country_id,
            'alert_type'  => $event->type,
            'event_type'  => $event->event_type,
            'severity'    => $event->severity,
            'occurred_at' => $event->created_at?->toIso8601String(),
        ];
    }

    private function isSuppressed(RiskAlertEvent $event): bool
    {
        if ($event->event_type !== 'retriggered') {
            return false;
        }

        return Cache::has($this->cacheKey($event));
    }

    private function markNotified(RiskAlertEvent $event): void
    {
        $minutes = (int) config('risk_notifications.suppression_minutes', 10);

        Cache::put($this->cacheKey($event), true, now()->addMinutes($minutes));
    }

    private function cacheKey(RiskAlertEvent $event): string
    {
        return "risk_alert_notified:{$event->risk_alert_id}";
    }
}
