<?php

namespace App\Console\Commands;

use App\Services\Governance\GovernanceTrajectoryService;
use Illuminate\Console\Command;
use Throwable;

class ComputeGovernanceTrajectoriesCommand extends Command
{
    protected $signature = 'governance:compute-trajectories';

    protected $description = 'Compute governance trajectory snapshots by comparing the last 24 h vs prior 24 h signals';

    public function __construct(
        private readonly GovernanceTrajectoryService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->service->compute();
            $this->info('Governance trajectories computed successfully.');
        } catch (Throwable $e) {
            $this->error("Trajectory computation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
