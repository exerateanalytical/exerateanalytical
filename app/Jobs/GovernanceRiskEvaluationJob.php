<?php

namespace App\Jobs;

use App\Models\GovernanceScore;
use App\Models\RiskSignal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GovernanceRiskEvaluationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $countryId,
        public readonly int $year
    ) {}

    public function handle(): void
    {
        $current = GovernanceScore::where('country_id', $this->countryId)
            ->where('year', $this->year)
            ->whereNull('region_id')
            ->latest('calculated_at')
            ->first();

        if (!$current) {
            Log::warning('No governance score found for risk evaluation', [
                'country_id' => $this->countryId,
                'year' => $this->year,
            ]);
            return;
        }

        $previous = GovernanceScore::where('country_id', $this->countryId)
            ->where('year', $this->year - 1)
            ->whereNull('region_id')
            ->latest('calculated_at')
            ->first();

        if (!$previous) return;

        $currentScore = (float) $current->composite_score;
        $previousScore = (float) $previous->composite_score;

        if ($previousScore == 0) return;

        $dropPercent = (($previousScore - $currentScore) / $previousScore) * 100;

        if ($dropPercent > 10) {
            $signal = RiskSignal::create([
                'country_id' => $this->countryId,
                'signal_type' => 'governance_score_drop',
                'severity' => $dropPercent > 20 ? 'critical' : 'high',
                'module' => 'Governance',
                'description' => "Governance score dropped by " . round($dropPercent, 2) . "% YoY.",
                'is_escalated' => false,
                'triggered_at' => now(),
            ]);

            // Check for 2 consecutive years of decline
            $twoPrevious = GovernanceScore::where('country_id', $this->countryId)
                ->where('year', $this->year - 2)
                ->whereNull('region_id')
                ->latest('calculated_at')
                ->first();

            if ($twoPrevious && (float) $previous->composite_score < (float) $twoPrevious->composite_score) {
                $signal->is_escalated = true;
                $signal->save();

                Log::critical('Governance score declined for 2 consecutive years', [
                    'country_id' => $this->countryId,
                    'year' => $this->year,
                ]);
            }
        }

        Log::info('Governance risk evaluation complete', [
            'country_id' => $this->countryId,
            'year' => $this->year,
            'drop_percent' => $dropPercent,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GovernanceRiskEvaluationJob failed', [
            'country_id' => $this->countryId,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
