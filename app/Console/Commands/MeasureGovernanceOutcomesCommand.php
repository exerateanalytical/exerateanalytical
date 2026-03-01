<?php

namespace App\Console\Commands;

use App\Models\GovernanceAction;
use App\Models\GovernanceActionOutcome;
use App\Services\Governance\GovernanceOutcomeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class MeasureGovernanceOutcomesCommand extends Command
{
    protected $signature   = 'governance:measure-outcomes';
    protected $description = 'CT-14 — Measure real-world outcomes of executed governance actions for each observation window';

    public function __construct(private readonly GovernanceOutcomeService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $total   = 0;
        $created = 0;
        $errors  = 0;

        // Process each window independently so a single large-scale scan
        // finds only the actions whose window has elapsed and lacks a row.
        foreach (['24h' => 24, '7d' => 168, '30d' => 720] as $window => $hours) {
            $cutoff = Carbon::now()->subHours($hours);

            // Actions old enough for this window that are still missing a row
            $alreadyMeasured = GovernanceActionOutcome::where('observation_window', $window)
                ->pluck('governance_action_id');

            $actions = GovernanceAction::whereIn('status', ['executed', 'approved'])
                ->whereNotNull('executed_at')
                ->where('executed_at', '<=', $cutoff)
                ->whereNotIn('id', $alreadyMeasured)
                ->get();

            foreach ($actions as $action) {
                $total++;
                try {
                    $outcomes = $this->service->measureOutcomes($action);
                    $created += $outcomes->count();
                } catch (Throwable $e) {
                    $errors++;
                    $this->warn(
                        "Outcome measurement failed for action {$action->id} [{$window}]: {$e->getMessage()}"
                    );
                }
            }
        }

        $this->info(
            "governance:measure-outcomes complete — {$created} outcomes created"
            . " across {$total} action/window pairs ({$errors} errors)."
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
