<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AuditSnapshotJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly string $entityType, public readonly string $entityId) {}

    public function handle(): void
    {
        Log::info('Creating audit snapshot', [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ]);
        // Snapshot logic will be implemented
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('AuditSnapshotJob failed permanently', [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'error' => $exception->getMessage(),
        ]);
    }
}
