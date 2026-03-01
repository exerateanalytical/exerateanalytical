<?php

namespace App\Services\Civic;

use App\Models\CivicSignalPriority;
use App\Models\FederationRegion;
use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\PolicyProposal;
use App\Models\Reaction;
use App\Models\ReputationEvent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * CT-11 Civic Signal Prioritization Engine.
 *
 * For each active poll, petition, and policy, computes:
 *
 *   participation_velocity = (last_24h − prev_24h) / max(prev_24h, 1)
 *   cross_region_factor    = 1.0 for global signals (null region_id),
 *                            1 / total_federation_regions for region-scoped signals.
 *   trust_impact           = avg(reputation_events.delta) linked to this signal.
 *   priority_score         = clamp(velocity×0.4 + cross_region×0.35 + |trust|×0.25, 0, 100)
 *
 * Each run appends immutable snapshots; rows are never updated.
 */
class SignalPriorityService
{
    public function compute(): void
    {
        $now          = Carbon::now();
        $cutoff24     = $now->copy()->subHours(24);
        $cutoff48     = $now->copy()->subHours(48);
        $totalRegions = max((int) FederationRegion::count(), 1);

        // ── Polls ─────────────────────────────────────────────────────────────
        Poll::where('status', 'active')
            ->each(function (Poll $poll) use ($now, $cutoff24, $cutoff48, $totalRegions): void {
                $engNow  = PollVote::where('poll_id', $poll->id)
                    ->where('created_at', '>=', $cutoff24)
                    ->count();
                $engPrev = PollVote::where('poll_id', $poll->id)
                    ->whereBetween('created_at', [$cutoff48, $cutoff24])
                    ->count();
                $this->snapshot('poll', $poll->id, $poll->region_id,
                    $engNow, $engPrev, Poll::class, $totalRegions, $now);
            });

        // ── Petitions ─────────────────────────────────────────────────────────
        Petition::whereIn('status', ['active', 'milestone_reached'])
            ->each(function (Petition $petition) use ($now, $cutoff24, $cutoff48, $totalRegions): void {
                $engNow  = PetitionSignature::where('petition_id', $petition->id)
                    ->where('created_at', '>=', $cutoff24)
                    ->count();
                $engPrev = PetitionSignature::where('petition_id', $petition->id)
                    ->whereBetween('created_at', [$cutoff48, $cutoff24])
                    ->count();
                $this->snapshot('petition', $petition->id, $petition->region_id,
                    $engNow, $engPrev, Petition::class, $totalRegions, $now);
            });

        // ── Policies (active stages = public_consultation, revision) ──────────
        PolicyProposal::whereIn('stage', ['public_consultation', 'revision'])
            ->each(function (PolicyProposal $policy) use ($now, $cutoff24, $cutoff48, $totalRegions): void {
                $morphType = PolicyProposal::class;
                $engNow  = Reaction::where('reactable_type', $morphType)
                    ->where('reactable_id', $policy->id)
                    ->where('created_at', '>=', $cutoff24)
                    ->count();
                $engPrev = Reaction::where('reactable_type', $morphType)
                    ->where('reactable_id', $policy->id)
                    ->whereBetween('created_at', [$cutoff48, $cutoff24])
                    ->count();
                $this->snapshot('policy', $policy->id, $policy->region_id,
                    $engNow, $engPrev, PolicyProposal::class, $totalRegions, $now);
            });
    }

    /**
     * Batch-hydrate a collection of CivicSignalPriority records with two
     * dynamic attributes:
     *   - signal_title  : the title of the underlying poll/petition/policy
     *   - signal_region : the name of its federation region (null = global)
     *
     * Attributes are set via setAttribute() so they survive resource serialisation.
     */
    public function hydrateSignals(Collection|EloquentCollection $priorities): void
    {
        $pollIds     = $priorities->where('signal_type', 'poll')->pluck('signal_id');
        $petitionIds = $priorities->where('signal_type', 'petition')->pluck('signal_id');
        $policyIds   = $priorities->where('signal_type', 'policy')->pluck('signal_id');

        $polls = $pollIds->isNotEmpty()
            ? Poll::whereIn('id', $pollIds)->with('region:id,name,code')->get()->keyBy('id')
            : collect();

        $petitions = $petitionIds->isNotEmpty()
            ? Petition::whereIn('id', $petitionIds)->with('region:id,name,code')->get()->keyBy('id')
            : collect();

        $policies = $policyIds->isNotEmpty()
            ? PolicyProposal::whereIn('id', $policyIds)->with('region:id,name,code')->get()->keyBy('id')
            : collect();

        foreach ($priorities as $priority) {
            $model = match ($priority->signal_type) {
                'poll'     => $polls->get($priority->signal_id),
                'petition' => $petitions->get($priority->signal_id),
                'policy'   => $policies->get($priority->signal_id),
                default    => null,
            };
            $priority->setAttribute('signal_title',  $model?->title ?? null);
            $priority->setAttribute('signal_region', $model?->region?->name ?? null);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function snapshot(
        string  $type,
        string  $signalId,
        ?string $regionId,
        int     $engNow,
        int     $engPrev,
        string  $contextClass,
        int     $totalRegions,
        Carbon  $now,
    ): void {
        $velocity    = ($engNow - $engPrev) / max($engPrev, 1);
        $crossRegion = $regionId === null ? 1.0 : 1.0 / $totalRegions;
        $trust       = (float) (ReputationEvent::where('context_type', $contextClass)
            ->where('context_id', $signalId)
            ->avg('delta') ?? 0.0);

        $score = max(0.0, min(100.0,
            $velocity * 0.4 + $crossRegion * 0.35 + abs($trust) * 0.25
        ));

        CivicSignalPriority::create([
            'signal_type'            => $type,
            'signal_id'              => $signalId,
            'priority_score'         => round($score,       3),
            'participation_velocity' => round($velocity,    3),
            'cross_region_factor'    => round($crossRegion, 3),
            'trust_impact'           => round($trust,       3),
            'calculated_at'          => $now,
            'created_at'             => $now,
        ]);
    }
}
