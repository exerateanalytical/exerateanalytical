<?php

namespace App\Services\Governance;

use App\Models\InstitutionalInfluence;
use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\Poll;
use App\Models\PolicyProposal;
use App\Models\ReputationEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Institutional & Actor Intelligence Service (CT-8).
 *
 * Aggregates per-user civic, engagement, and trust signals from the last
 * 30 days, derives a normalised influence score (0–100), assigns an
 * influence category, and persists read-only snapshot rows.
 *
 * Safety: this service never creates or modifies governance actions.
 */
class InstitutionalInfluenceService
{
    private const MIN_SCORE    = 0.5;
    private const WINDOW_DAYS  = 30;
    private const PRUNE_HOURS  = 2;

    /**
     * Compute influence signals for the current cycle and persist them.
     *
     * @return Collection<InstitutionalInfluence>
     */
    public function compute(): Collection
    {
        $now   = Carbon::now();
        $since = $now->copy()->subDays(self::WINDOW_DAYS);

        [$pollCounts, $petitionCounts, $signatureCounts, $policyCounts,
         $reactionCounts, $trustDeltas, $regionMap]
            = $this->loadData($since);

        // Collect every actor ID that has any measurable activity
        $actorIds = collect()
            ->merge($pollCounts->keys())
            ->merge($petitionCounts->keys())
            ->merge($signatureCounts->keys())
            ->merge($policyCounts->keys())
            ->merge($trustDeltas->keys())
            ->unique()
            ->values();

        if ($actorIds->isEmpty()) {
            return collect();
        }

        // Build raw (un-normalised) component scores per actor
        $rawData = [];

        foreach ($actorIds as $actorId) {
            $polls      = (int)   ($pollCounts[$actorId]      ?? 0);
            $petitions  = (int)   ($petitionCounts[$actorId]  ?? 0);
            $signatures = (int)   ($signatureCounts[$actorId] ?? 0);
            $policies   = (int)   ($policyCounts[$actorId]    ?? 0);
            $reactions  = (int)   ($reactionCounts[$actorId]  ?? 0);
            $trustDelta = (float) ($trustDeltas[$actorId]     ?? 0.0);

            $rawData[$actorId] = [
                'polls'         => $polls,
                'petitions'     => $petitions,
                'signatures'    => $signatures,
                'policies'      => $policies,
                'reactions'     => $reactions,
                'trust_delta'   => $trustDelta,
                'participation' => $polls * 5 + $petitions * 4 + $policies * 8,
                'engagement'    => $signatures * 1.5 + $reactions * 0.3,
                'region_id'     => $regionMap[$actorId] ?? null,
            ];
        }

        // Normalisation denominators — avoid division by zero
        $maxParticipation = max(1.0, collect($rawData)->max('participation'));
        $maxEngagement    = max(1.0, collect($rawData)->max('engagement'));
        $maxAbsTrust      = max(
            1.0,
            collect($rawData)->max(fn ($d) => abs($d['trust_delta']))
        );

        $signals = collect();

        foreach ($rawData as $actorId => $data) {
            try {
                // 40 pts from participation, 30 from engagement, 30 from trust magnitude
                $participationWeight = ($data['participation'] / $maxParticipation) * 40.0;
                $engagementWeight    = ($data['engagement']    / $maxEngagement)    * 30.0;
                $trustAbsNorm        = min(1.0, abs($data['trust_delta']) / $maxAbsTrust);
                $trustWeight         = $trustAbsNorm * 30.0;

                $score = round($participationWeight + $engagementWeight + $trustWeight, 3);

                if ($score < self::MIN_SCORE) continue;

                $activityVolume = $data['polls'] + $data['petitions']
                    + $data['signatures'] + $data['policies'];

                $signals->push([
                    'actor_id'           => $actorId,
                    'region_id'          => $data['region_id'],
                    'influence_category' => $this->deriveCategory($data),
                    'influence_score'    => min(100.0, $score),
                    'activity_volume'    => $activityVolume,
                    'trust_delta'        => $data['trust_delta'],
                    'activity_breakdown' => [
                        'polls_created'      => $data['polls'],
                        'petitions_created'  => $data['petitions'],
                        'petitions_signed'   => $data['signatures'],
                        'policies_created'   => $data['policies'],
                        'reactions_received' => $data['reactions'],
                    ],
                    'calculated_at' => $now,
                ]);
            } catch (Throwable) {}
        }

        // Prune rows older than the retention window, then insert the fresh batch
        InstitutionalInfluence::where(
            'calculated_at', '<', $now->copy()->subHours(self::PRUNE_HOURS)
        )->delete();

        foreach ($signals as $signal) {
            try {
                InstitutionalInfluence::create($signal);
            } catch (Throwable) {}
        }

        // Return this cycle's rows (within 60 s of $now for safety)
        return InstitutionalInfluence::with('actor:id,name,email', 'region:id,code,name')
            ->where('calculated_at', '>=', $now->copy()->subMinute())
            ->orderByDesc('influence_score')
            ->get();
    }

    // ── Category assignment ───────────────────────────────────────────────────

    private function deriveCategory(array $data): string
    {
        $trustDelta = $data['trust_delta'];
        $policies   = $data['policies'];
        $polls      = $data['polls'];
        $petitions  = $data['petitions'];

        // Strong negative trust trend → volatility source
        if ($trustDelta < -2.0) {
            return 'volatility_source';
        }

        // Strong positive trust with some civic activity → trust stabilizer
        if ($trustDelta > 2.0 && ($polls + $petitions + $policies) >= 1) {
            return 'trust_stabilizer';
        }

        // Significant policy authorship
        if ($policies >= 2) {
            return 'policy_driver';
        }

        // Dominant civic participation (polls + petitions)
        if ($polls + $petitions >= 2) {
            return 'civic_leader';
        }

        // Fallback: whichever sub-score is highest
        $policyComponent = $policies * 8;
        $civicComponent  = $polls * 5 + $petitions * 4;

        return $policyComponent > $civicComponent ? 'policy_driver' : 'civic_leader';
    }

    // ── Data loaders ──────────────────────────────────────────────────────────

    private function loadData(Carbon $since): array
    {
        // Polls created per user
        $pollCounts = collect();
        try {
            $pollCounts = Poll::selectRaw('creator_id, COUNT(*) as cnt')
                ->whereNotNull('creator_id')
                ->where('created_at', '>=', $since)
                ->groupBy('creator_id')
                ->pluck('cnt', 'creator_id');
        } catch (Throwable) {}

        // Petitions created per user
        $petitionCounts = collect();
        try {
            $petitionCounts = Petition::selectRaw('creator_id, COUNT(*) as cnt')
                ->whereNotNull('creator_id')
                ->where('created_at', '>=', $since)
                ->groupBy('creator_id')
                ->pluck('cnt', 'creator_id');
        } catch (Throwable) {}

        // Petition signatures per user
        $signatureCounts = collect();
        try {
            $signatureCounts = PetitionSignature::selectRaw('user_id, COUNT(*) as cnt')
                ->whereNotNull('user_id')
                ->where('created_at', '>=', $since)
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id');
        } catch (Throwable) {}

        // Policy proposals created per user
        $policyCounts = collect();
        try {
            $policyCounts = PolicyProposal::selectRaw('creator_id, COUNT(*) as cnt')
                ->whereNotNull('creator_id')
                ->where('created_at', '>=', $since)
                ->groupBy('creator_id')
                ->pluck('cnt', 'creator_id');
        } catch (Throwable) {}

        // Reactions received per user (on polls + petitions + policies they created)
        $reactionCounts = collect();
        try {
            $pollReactions = DB::table('reactions')
                ->join('polls', function ($j) {
                    $j->on('reactions.reactable_id', '=', 'polls.id')
                      ->where('reactions.reactable_type', '=', Poll::class);
                })
                ->whereNotNull('polls.creator_id')
                ->where('reactions.created_at', '>=', $since)
                ->selectRaw('polls.creator_id as user_id, COUNT(*) as cnt')
                ->groupBy('polls.creator_id')
                ->pluck('cnt', 'user_id');

            $petitionReactions = DB::table('reactions')
                ->join('petitions', function ($j) {
                    $j->on('reactions.reactable_id', '=', 'petitions.id')
                      ->where('reactions.reactable_type', '=', Petition::class);
                })
                ->whereNotNull('petitions.creator_id')
                ->where('reactions.created_at', '>=', $since)
                ->selectRaw('petitions.creator_id as user_id, COUNT(*) as cnt')
                ->groupBy('petitions.creator_id')
                ->pluck('cnt', 'user_id');

            $policyReactions = DB::table('reactions')
                ->join('policy_proposals', function ($j) {
                    $j->on('reactions.reactable_id', '=', 'policy_proposals.id')
                      ->where('reactions.reactable_type', '=', PolicyProposal::class);
                })
                ->whereNotNull('policy_proposals.creator_id')
                ->where('reactions.created_at', '>=', $since)
                ->selectRaw('policy_proposals.creator_id as user_id, COUNT(*) as cnt')
                ->groupBy('policy_proposals.creator_id')
                ->pluck('cnt', 'user_id');

            // Merge: sum counts across all three content types per user
            $reactionCounts = $pollReactions->toBase();
            foreach ($petitionReactions as $userId => $cnt) {
                $reactionCounts[$userId] = ($reactionCounts[$userId] ?? 0) + $cnt;
            }
            foreach ($policyReactions as $userId => $cnt) {
                $reactionCounts[$userId] = ($reactionCounts[$userId] ?? 0) + $cnt;
            }
        } catch (Throwable) {}

        // Trust delta per user — sum of reputation_event deltas in window
        $trustDeltas = collect();
        try {
            $trustDeltas = ReputationEvent::selectRaw('user_id, SUM(delta) as trust_delta')
                ->whereNotNull('user_id')
                ->where('created_at', '>=', $since)
                ->groupBy('user_id')
                ->pluck('trust_delta', 'user_id')
                ->map(fn ($v) => (float) $v);
        } catch (Throwable) {}

        // Dominant region per user: most recent poll region, falling back to petitions
        $regionMap = collect();
        try {
            $pollRegions = Poll::selectRaw('creator_id, region_id')
                ->whereNotNull('creator_id')
                ->whereNotNull('region_id')
                ->where('created_at', '>=', $since)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('creator_id')
                ->map(fn ($rows) => $rows->first()->region_id);

            $petitionRegions = Petition::selectRaw('creator_id, region_id')
                ->whereNotNull('creator_id')
                ->whereNotNull('region_id')
                ->where('created_at', '>=', $since)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('creator_id')
                ->map(fn ($rows) => $rows->first()->region_id);

            // Petition regions fill gaps; poll regions take priority
            $regionMap = $petitionRegions->merge($pollRegions);
        } catch (Throwable) {}

        return [
            $pollCounts,
            $petitionCounts,
            $signatureCounts,
            $policyCounts,
            $reactionCounts,
            $trustDeltas,
            $regionMap,
        ];
    }
}
