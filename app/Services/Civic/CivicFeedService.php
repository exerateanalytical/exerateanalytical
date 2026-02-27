<?php

namespace App\Services\Civic;

use App\Models\Petition;
use App\Models\Poll;
use App\Models\PolicyProposal;
use Illuminate\Support\Collection;

class CivicFeedService
{
    public function getFeed(array $filters = []): Collection
    {
        $regionId = $filters['region_id'] ?? null;
        $type     = $filters['type']      ?? null;     // poll | petition | policy
        $sort     = $filters['sort']      ?? 'recent'; // recent | impact

        $items = collect();

        if (! $type || $type === 'poll') {
            $items = $items->merge($this->fetchPolls($regionId));
        }

        if (! $type || $type === 'petition') {
            $items = $items->merge($this->fetchPetitions($regionId));
        }

        if (! $type || $type === 'policy') {
            $items = $items->merge($this->fetchPolicies($regionId));
        }

        return $sort === 'impact'
            ? $items->sortByDesc('engagement_score')->values()
            : $items->sortByDesc('created_at')->values();
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function fetchPolls(?string $regionId): Collection
    {
        return Poll::withCount('reactions')
            ->with('creator:id,reputation_score')
            ->where('status', 'active')
            ->when($regionId, fn ($q) => $q->where('region_id', $regionId))
            ->get()
            ->map(fn (Poll $poll) => [
                'id'               => $poll->id,
                'type'             => 'poll',
                'title'            => $poll->title,
                'region_id'        => $poll->region_id,
                'status'           => $poll->status,
                'created_at'       => $poll->created_at?->toIso8601String(),
                'engagement_score' => $this->applyVisibilityWeight(
                    round(($poll->total_votes * 0.6) + $poll->reactions_count, 3),
                    (float) ($poll->creator?->reputation_score ?? 0),
                ),
            ]);
    }

    private function fetchPetitions(?string $regionId): Collection
    {
        return Petition::withCount('reactions')
            ->with('creator:id,reputation_score')
            ->where('status', 'active')
            ->when($regionId, fn ($q) => $q->where('region_id', $regionId))
            ->get()
            ->map(fn (Petition $petition) => [
                'id'               => $petition->id,
                'type'             => 'petition',
                'title'            => $petition->title,
                'region_id'        => $petition->region_id,
                'status'           => $petition->status,
                'created_at'       => $petition->created_at?->toIso8601String(),
                'engagement_score' => $this->applyVisibilityWeight(
                    round(($petition->signature_count * 0.7) + $petition->reactions_count, 3),
                    (float) ($petition->creator?->reputation_score ?? 0),
                ),
            ]);
    }

    private function fetchPolicies(?string $regionId): Collection
    {
        return PolicyProposal::withCount('reactions')
            ->with('creator:id,reputation_score')
            ->where('status', 'active')
            ->when($regionId, fn ($q) => $q->where('region_id', $regionId))
            ->get()
            ->map(fn (PolicyProposal $policy) => [
                'id'               => $policy->id,
                'type'             => 'policy',
                'title'            => $policy->title,
                'region_id'        => $policy->region_id,
                'status'           => $policy->status,
                'created_at'       => $policy->created_at?->toIso8601String(),
                'engagement_score' => $this->applyVisibilityWeight(
                    round($policy->reactions_count * 0.8, 3),
                    (float) ($policy->creator?->reputation_score ?? 0),
                ),
            ]);
    }

    /**
     * Apply creator trust weighting to an engagement score.
     *
     * final_score = engagement_score × log10(1 + reputation_score)
     *
     * A score of 0 (new/unknown creator) gives log10(1) = 0.
     * A score of 9  gives log10(10) = 1.0 (neutral multiplier at tier boundary).
     * A score of 99 gives log10(100) = 2.0 (doubled).
     */
    private function applyVisibilityWeight(float $engagementScore, float $reputationScore): float
    {
        return round($engagementScore * log10(1 + $reputationScore), 3);
    }
}
