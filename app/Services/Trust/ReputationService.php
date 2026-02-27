<?php

namespace App\Services\Trust;

use App\Models\ReputationEvent;
use App\Models\User;

class ReputationService
{
    /** Tier thresholds (lower bound inclusive). */
    private const TIERS = [
        400 => 'Institutional',
        150 => 'Steward',
        50  => 'Trusted',
        10  => 'Contributor',
        0   => 'Citizen',
    ];

    /**
     * Sum all reputation_events deltas for a user and return the total.
     */
    public function calculateUserScore(User $user): float
    {
        return (float) ReputationEvent::where('user_id', $user->id)->sum('delta');
    }

    /**
     * Derive the tier string from a numeric score.
     */
    public function computeTier(float $score): string
    {
        foreach (self::TIERS as $threshold => $tier) {
            if ($score >= $threshold) {
                return $tier;
            }
        }

        return 'Citizen';
    }

    /**
     * Recalculate and persist reputation_score + reputation_tier for the user.
     * Called automatically by ReputationEventObserver after every new event.
     */
    public function recalculate(User $user): void
    {
        $score = $this->calculateUserScore($user);
        $tier  = $this->computeTier($score);

        // Bypass mass-assignment — update directly to avoid guard issues
        $user->forceFill([
            'reputation_score' => $score,
            'reputation_tier'  => $tier,
        ])->saveQuietly();
    }
}
