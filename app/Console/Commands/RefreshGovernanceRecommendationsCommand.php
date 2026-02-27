<?php

namespace App\Console\Commands;

use App\Services\Governance\GovernanceAdvisorService;
use App\Services\Governance\GovernanceScenarioService;
use Illuminate\Console\Command;
use Throwable;

class RefreshGovernanceRecommendationsCommand extends Command
{
    protected $signature = 'governance:refresh-recommendations';

    protected $description = 'Refresh AI Governance Advisor recommendations and counterfactual scenarios';

    public function __construct(
        private readonly GovernanceAdvisorService  $advisor,
        private readonly GovernanceScenarioService $scenarios,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ── Step 1: Refresh recommendations ───────────────────────────────────
        $recs = $this->advisor->generateRecommendations();

        $this->info("Governance Advisor: {$recs->count()} active recommendation(s) generated.");

        // ── Step 2: Generate counterfactual scenarios for each new rec ────────
        foreach ($recs as $rec) {
            $this->line("  [{$rec->severity}] {$rec->title} — confidence {$rec->confidence_score}%");

            try {
                $generated = $this->scenarios->generateForRecommendation($rec);
                $this->line("    → {$generated->count()} scenario(s) generated.");
            } catch (Throwable $e) {
                $this->warn("    → Scenario generation failed for rec {$rec->id}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
