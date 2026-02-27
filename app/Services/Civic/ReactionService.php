<?php

namespace App\Services\Civic;

use App\Models\ModerationLog;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReactionService
{
    public function react(User $user, Model $reactable, string $type): Reaction
    {
        return DB::transaction(function () use ($user, $reactable, $type) {
            $existing = Reaction::where('user_id', $user->id)
                ->where('reactable_id', $reactable->id)
                ->where('reactable_type', get_class($reactable))
                ->first();

            if ($existing) {
                if ($existing->type === $type) {
                    // Idempotent — same reaction already recorded
                    return $existing;
                }

                // User changed their reaction type — update in place
                $existing->update(['type' => $type]);
                $reaction = $existing->fresh();
            } else {
                $reaction = Reaction::create([
                    'user_id'        => $user->id,
                    'reactable_id'   => $reactable->id,
                    'reactable_type' => get_class($reactable),
                    'type'           => $type,
                ]);
            }

            // A "report" reaction automatically opens a moderation log entry
            if ($type === 'report') {
                ModerationLog::create([
                    'moderator_id' => $user->id,
                    'subject_id'   => $reactable->id,
                    'subject_type' => get_class($reactable),
                    'action'       => 'flagged',
                    'reason'       => 'Reported via user reaction (user_id: ' . $user->id . ')',
                ]);
            }

            return $reaction;
        });
    }
}
