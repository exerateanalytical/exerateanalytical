<?php

namespace App\Jobs;

use App\Models\RiskAlertEvent;
use App\Services\Risk\RiskNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRiskNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public readonly string $eventId)
    {
        $this->onQueue('risk-notifications');
    }

    public function handle(RiskNotificationService $service): void
    {
        $event = RiskAlertEvent::find($this->eventId);

        if ($event === null) {
            return;
        }

        $service->dispatchNow($event);
    }

    public function failed(\Throwable $e): void
    {
        $event = RiskAlertEvent::find($this->eventId);

        if ($event === null) {
            return;
        }

        $metadata = $event->metadata ?? [];
        $metadata['notification_failed'] = true;
        $event->update(['metadata' => $metadata]);
    }
}
