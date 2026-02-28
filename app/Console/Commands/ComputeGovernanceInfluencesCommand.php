<?php

namespace App\Console\Commands;

use App\Services\Governance\GovernanceInfluenceService;
use Illuminate\Console\Command;
use Throwable;

class ComputeGovernanceInfluencesCommand extends Command
{
    protected $signature = 'governance:compute-influences';

    protected $description = 'Compute governance causality and influence signals from current system data';

    public function __construct(
        private readonly GovernanceInfluenceService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $signals = $this->service->compute();
            $this->info("Governance Influence: {$signals->count()} signal(s) computed.");

            foreach ($signals as $signal) {
                $direction = $signal->impact_direction === 'up' ? '↑ risk' : '↓ risk';
                $region    = $signal->region?->code ?? 'global';
                $this->line(sprintf(
                    '  [%s] %s — %s (score %.1f) %s',
                    $region,
                    $signal->influence_type,
                    $signal->source_type,
                    $signal->influence_score,
                    $direction,
                ));
            }
        } catch (Throwable $e) {
            $this->error("Influence computation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
