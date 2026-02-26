<?php

namespace App\Console\Commands;

use App\Models\FederationRegion;
use App\Services\Federation\RegionalSystemicSnapshotService;
use Illuminate\Console\Command;

class PublishRegionalSnapshotCommand extends Command
{
    protected $signature = 'risk:publish-regional-snapshot';

    protected $description = 'Generate and store a systemic snapshot for every federation region.';

    public function __construct(
        private readonly RegionalSystemicSnapshotService $snapshotService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $regions = FederationRegion::all();

        if ($regions->isEmpty()) {
            $this->warn('No federation regions found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($regions as $region) {
            $this->snapshotService->generate($region->id);
            $count++;
        }

        $this->info("Published {$count} regional snapshot(s).");

        return self::SUCCESS;
    }
}
