<?php

namespace App\Console\Commands;

use App\Services\Governance\GovernanceAdvisorService;
use Illuminate\Console\Command;

class RefreshGovernanceRecommendationsCommand extends Command
{
    protected $signature = 'governance:refresh-recommendations';

    protected $description = 'Refresh AI Governance Advisor recommendations based on current system signals';

    public function __construct(
        private readonly GovernanceAdvisorService $advisor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $recs = $this->advisor->generateRecommendations();

        $this->info("Governance Advisor: {$recs->count()} active recommendation(s) generated.");

        foreach ($recs as $rec) {
            $this->line("  [{$rec->severity}] {$rec->title} — confidence {$rec->confidence_score}%");
        }

        return Command::SUCCESS;
    }
}
