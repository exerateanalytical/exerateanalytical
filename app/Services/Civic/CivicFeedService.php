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
        $type     = $filters['type']      ?? null;   // poll | petition | policy
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
                'engagement_score' => round(($poll->total_votes * 0.6) + $poll->reactions_count, 3),
            ]);
    }

    private function fetchPetitions(?string $regionId): Collection
    {
        return Petition::withCount('reactions')
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
                'engagement_score' => round(($petition->signature_count * 0.7) + $petition->reactions_count, 3),
            ]);
    }

    private function fetchPolicies(?string $regionId): Collection
    {
        return PolicyProposal::withCount('reactions')
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
                'engagement_score' => round($policy->reactions_count * 0.8, 3),
            ]);
    }
}
