<?php

namespace App\Services\Trust;

use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\PolicyProposal;
use App\Models\PollVote;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Carbon;

class BadgeService
{
    /** All badge codes managed by this service. */
    public const FIRST_VOTE       = 'FIRST_VOTE';
    public const ACTIVE_CITIZEN   = 'ACTIVE_CITIZEN';
    public const PETITION_LEADER  = 'PETITION_LEADER';
    public const POLICY_AUTHOR    = 'POLICY_AUTHOR';
    public const TRUSTED_MEMBER   = 'TRUSTED_MEMBER';

    /** Tiers that qualify for TRUSTED_MEMBER. */
    private const TRUSTED_TIERS = ['Trusted', 'Steward', 'Institutional'];

    /**
     * Evaluate and award all applicable badges for the user.
     * Idempotent: already-awarded badges are silently skipped.
     */
    public function recalculate(User $user): void
    {
        $voteCount = PollVote::where('user_id', $user->id)->count();

        if ($voteCount >= 1) {
            $this->award($user, self::FIRST_VOTE);
        }

        if ($voteCount >= 10) {
            $this->award($user, self::ACTIVE_CITIZEN);
        }

        $hasMilestonePetition = Petition::where('creator_id', $user->id)
            ->whereIn('status', ['milestone_reached', 'submitted'])
            ->exists();

        if ($hasMilestonePetition) {
            $this->award($user, self::PETITION_LEADER);
        }

        $hasPolicyProposal = PolicyProposal::where('creator_id', $user->id)->exists();

        if ($hasPolicyProposal) {
            $this->award($user, self::POLICY_AUTHOR);
        }

        if (in_array($user->reputation_tier, self::TRUSTED_TIERS, true)) {
            $this->award($user, self::TRUSTED_MEMBER);
        }
    }

    /**
     * Idempotently award a badge.
     * Uses firstOrCreate so a duplicate unique constraint is never hit.
     */
    private function award(User $user, string $badgeCode): void
    {
        UserBadge::firstOrCreate(
            ['user_id' => $user->id, 'badge_code' => $badgeCode],
            ['awarded_at' => Carbon::now()],
        );
    }
}
