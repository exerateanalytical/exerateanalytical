<?php

namespace App\Services\Risk;

use App\Jobs\SendRiskNotificationJob;
use App\Models\RiskAlertEvent;
use App\Services\Risk\Notifications\NotificationChannelInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RiskNotificationService
{
    public function __construct(
        private readonly RiskSubscriptionResolver $resolver,
    ) {}

    public function notify(RiskAlertEvent $event): void
    {
        if ($this->isSuppressed($event)) {
            Log::debug('Risk notification suppressed (retrigger within window)', [
                'event_id'   => $event->id,
                'alert_id'   => $event->risk_alert_id,
                'country_id' => $event->country_id,
                'event_type' => $event->event_type,
                'severity'   => $event->severity,
            ]);

            return;
        }

        DB::afterCommit(fn () => SendRiskNotificationJob::dispatch($event->id));
    }

    public function dispatchNow(RiskAlertEvent $event): void
    {
        $subscriptions = $this->resolver->resolve($event);

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = $this->buildPayload($event);

        foreach ($subscriptions as $subscription) {
            try {
                /** @var NotificationChannelInterface $channel */
                $channel = app()->makeWith($subscription->channel, [
                    'channelConfig' => $subscription->channel_config ?? [],
                ]);

                $channel->send($payload);
            } catch (\Throwable $e) {
                Log::error('Risk notification channel failure', [
                    'channel'         => $subscription->channel,
                    'subscription_id' => $subscription->id,
                    'event_id'        => $event->id,
                    'alert_id'        => $event->risk_alert_id,
                    'country_id'      => $event->country_id,
                    'event_type'      => $event->event_type,
                    'severity'        => $event->severity,
                    'exception'       => $e::class,
                    'error'           => $e->getMessage(),
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
