<?php

namespace App\Services\Civic;

use App\Models\PolicyProposal;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PolicyService
{
    /** Ordered stage progression */
    private const STAGE_FLOW = [
        'draft'               => 'public_consultation',
        'public_consultation' => 'revision',
        'revision'            => 'finalized',
        'finalized'           => 'archived',
    ];

    public function createProposal(User $user, array $data): PolicyProposal
    {
        return DB::transaction(function () use ($user, $data) {
            $proposal = PolicyProposal::create([
                'creator_id' => $user->id,
                'region_id'  => $data['region_id'] ?? null,
                'title'      => $data['title'],
                'abstract'   => $data['abstract'],
                'full_text'  => $data['full_text'],
                'stage'      => 'draft',
                'status'     => 'active',
            ]);

            ReputationEvent::create([
                'user_id'      => $user->id,
                'event_type'   => 'policy_created',
                'delta'        => 1.0,
                'context_type' => PolicyProposal::class,
                'context_id'   => $proposal->id,
            ]);

            return $proposal;
        });
    }

    public function changeStage(User $user, PolicyProposal $proposal, string $targetStage): PolicyProposal
    {
        return DB::transaction(function () use ($user, $proposal, $targetStage) {
            if ($proposal->status !== 'active') {
                throw new RuntimeException('Cannot advance stage on a non-active proposal.');
            }

            if (
                $proposal->creator_id !== $user->id
                && ! $user->hasRole(['SuperAdmin', 'moderator'])
            ) {
                throw new RuntimeException(
                    'Only the creator or a moderator can change the proposal stage.',
                    403,
                );
            }

            $expectedNext = self::STAGE_FLOW[$proposal->stage] ?? null;

            if ($expectedNext === null) {
                throw new RuntimeException('This proposal has already reached its terminal stage.');
            }

            if ($targetStage !== $expectedNext) {
                throw new RuntimeException(
                    "Invalid stage transition. Expected next stage: {$expectedNext}.",
                );
            }

            $proposal->update(['stage' => $targetStage]);

            // Reputation bonus for publishing to consultation
            if ($targetStage === 'public_consultation') {
                ReputationEvent::create([
                    'user_id'      => $user->id,
                    'event_type'   => 'policy_published_to_consultation',
                    'delta'        => 3.0,
                    'context_type' => PolicyProposal::class,
                    'context_id'   => $proposal->id,
                ]);
            }

            return $proposal->fresh();
        });
    }
}
