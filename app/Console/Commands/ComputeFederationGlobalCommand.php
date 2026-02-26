<?php

namespace App\Console\Commands;

use App\Services\Federation\FederationAggregationService;
use Illuminate\Console\Command;

class ComputeFederationGlobalCommand extends Command
{
    protected $signature = 'risk:compute-federation-global';

    protected $description = 'Aggregate regional snapshots into a global federated snapshot.';

    public function __construct(
        private readonly FederationAggregationService $aggregationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $snapshot = $this->aggregationService->computeGlobal();

        $this->info("Global snapshot computed — {$snapshot->region_count} region(s) aggregated.");

        return self::SUCCESS;
    }
}
