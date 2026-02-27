<?php

namespace App\Services\Civic;

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PollService
{
    public function createPoll(User $user, array $data): Poll
    {
        return DB::transaction(function () use ($user, $data) {
            $poll = Poll::create([
                'creator_id'           => $user->id,
                'region_id'            => $data['region_id'] ?? null,
                'title'                => $data['title'],
                'description'          => $data['description'] ?? null,
                'visibility'           => $data['visibility'] ?? 'public',
                'type'                 => $data['type'] ?? 'standard',
                'options'              => $data['options'],
                'starts_at'            => $data['starts_at'] ?? null,
                'ends_at'              => $data['ends_at'] ?? null,
                'allow_multiple_votes' => $data['allow_multiple_votes'] ?? false,
                'verified_only'        => $data['verified_only'] ?? false,
                'status'               => 'active',
            ]);

            $this->recordReputation($user->id, 'poll_created', 1.0, Poll::class, $poll->id);

            return $poll;
        });
    }

    public function vote(User $user, Poll $poll, array $selectedOptions): PollVote
    {
        return DB::transaction(function () use ($user, $poll, $selectedOptions) {
            $this->assertVotingAllowed($user, $poll, $selectedOptions);

            $vote = PollVote::create([
                'poll_id'          => $poll->id,
                'user_id'          => $user->id,
                'selected_options' => $selectedOptions,
                'weight'           => $this->resolveWeight($user),
                'voted_at'         => Carbon::now(),
            ]);

            $poll->increment('total_votes');

            $this->recordReputation($user->id, 'poll_voted', 0.2, Poll::class, $poll->id);

            return $vote;
        });
    }

    public function closePoll(User $user, Poll $poll): Poll
    {
        if ($poll->creator_id !== $user->id && ! $user->hasRole('SuperAdmin')) {
            throw new RuntimeException('Only the creator or a SuperAdmin can close this poll.', 403);
        }

        $poll->update(['status' => 'closed']);

        return $poll->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function assertVotingAllowed(User $user, Poll $poll, array $selectedOptions): void
    {
        if ($poll->status !== 'active') {
            throw new RuntimeException('This poll is not accepting votes.');
        }

        if ($poll->verified_only && ! $user->email_verified_at) {
            throw new RuntimeException('Only verified users can vote on this poll.');
        }

        $now = Carbon::now();

        if ($poll->starts_at && $now->lt($poll->starts_at)) {
            throw new RuntimeException('Voting has not started yet.');
        }

        if ($poll->ends_at && $now->gt($poll->ends_at)) {
            throw new RuntimeException('The voting window for this poll has closed.');
        }

        if (PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->exists()) {
            throw new RuntimeException('You have already voted on this poll.');
        }

        if (! $poll->allow_multiple_votes && count($selectedOptions) > 1) {
            throw new RuntimeException('This poll allows only one selected option.');
        }
    }

    private function resolveWeight(User $user): float
    {
        $score = ReputationEvent::where('user_id', $user->id)->sum('delta');

        return max(1.0, (float) $score);
    }

    private function recordReputation(
        string $userId,
        string $eventType,
        float $delta,
        string $contextType,
        string $contextId,
    ): void {
        ReputationEvent::create([
            'user_id'      => $userId,
            'event_type'   => $eventType,
            'delta'        => $delta,
            'context_type' => $contextType,
            'context_id'   => $contextId,
        ]);
    }
}
