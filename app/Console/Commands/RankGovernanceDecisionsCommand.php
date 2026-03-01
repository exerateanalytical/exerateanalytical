<?php

namespace App\Console\Commands;

use App\Models\GovernanceRecommendation;
use App\Services\Governance\DecisionRankingService;
use Illuminate\Console\Command;
use Throwable;

class RankGovernanceDecisionsCommand extends Command
{
    protected $signature   = 'governance:rank-decisions';
    protected $description = 'CT-13 — Score and rank governance decision scenarios for all active recommendations';

    public function __construct(private readonly DecisionRankingService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $recommendations = GovernanceRecommendation::where('status', 'active')->get();

        if ($recommendations->isEmpty()) {
            $this->info('No active recommendations to rank.');
            return Command::SUCCESS;
        }

        $ranked = 0;
        foreach ($recommendations as $rec) {
            try {
                $results = $this->service->rankForRecommendation($rec);
                if ($results->isNotEmpty()) {
                    $ranked++;
                }
            } catch (Throwable $e) {
                $this->warn("Ranking failed for recommendation {$rec->id}: {$e->getMessage()}");
            }
        }

        $this->info("Decision rankings generated for {$ranked} / {$recommendations->count()} recommendations.");

        return Command::SUCCESS;
    }
}
