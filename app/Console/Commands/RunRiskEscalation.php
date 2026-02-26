<?php

namespace App\Console\Commands;

use App\Services\Risk\RiskEscalationService;
use Illuminate\Console\Command;

class RunRiskEscalation extends Command
{
    protected $signature = 'risk:escalate';

    protected $description = 'Escalate unacknowledged active risk alerts that have exceeded their severity thresholds';

    public function __construct(
        private readonly RiskEscalationService $escalationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->escalationService->run();

        $this->info("Escalated {$count} alert(s).");

        return Command::SUCCESS;
    }
}
