<?php

namespace App\Events;

use App\Models\RiskAlertEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskAlertStreamed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly RiskAlertEvent $event,
        public readonly ?string $regionCode = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        $channel = $this->regionCode
            ? "risk-stream.{$this->regionCode}"
            : 'risk-stream.global';

        return new PrivateChannel($channel);
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->event->id,
            'country_id' => $this->event->country_id,
            'type'       => $this->event->type,
            'event_type' => $this->event->event_type,
            'severity'   => $this->event->severity,
            'created_at' => $this->event->created_at?->toIso8601String(),
        ];
    }
}
