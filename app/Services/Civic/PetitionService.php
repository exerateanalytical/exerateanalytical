<?php

namespace App\Services\Civic;

use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PetitionService
{
    public function createPetition(User $user, array $data): Petition
    {
        return DB::transaction(function () use ($user, $data) {
            $petition = Petition::create([
                'creator_id'     => $user->id,
                'region_id'      => $data['region_id'] ?? null,
                'title'          => $data['title'],
                'summary'        => $data['summary'],
                'body'           => $data['body'],
                'signature_goal' => $data['signature_goal'] ?? 100,
                'deadline'       => $data['deadline'] ?? null,
                'status'         => 'active',
            ]);

            $this->record($user->id, 'petition_created', 2.0, Petition::class, $petition->id);

            return $petition;
        });
    }

    public function sign(User $user, Petition $petition): PetitionSignature
    {
        return DB::transaction(function () use ($user, $petition) {
            $this->assertSigningAllowed($user, $petition);

            $weight = max(1.0, (float) ReputationEvent::where('user_id', $user->id)->sum('delta'));

            $signature = PetitionSignature::create([
                'petition_id' => $petition->id,
                'user_id'     => $user->id,
                'weight'      => $weight,
                'signed_at'   => Carbon::now(),
            ]);

            $petition->increment('signature_count');
            $petition->refresh();

            if (
                $petition->signature_count >= $petition->signature_goal
                && $petition->status === 'active'
            ) {
                $petition->update(['status' => 'milestone_reached']);
            }

            $this->record($user->id, 'petition_signed', 0.5, Petition::class, $petition->id);

            return $signature;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function assertSigningAllowed(User $user, Petition $petition): void
    {
        if (! in_array($petition->status, ['active', 'milestone_reached'], true)) {
            throw new RuntimeException('This petition is not open for signatures.');
        }

        if ($petition->deadline && Carbon::now()->gt($petition->deadline)) {
            throw new RuntimeException('The deadline for this petition has passed.');
        }

        if (
            PetitionSignature::where('petition_id', $petition->id)
                ->where('user_id', $user->id)
                ->exists()
        ) {
            throw new RuntimeException('You have already signed this petition.');
        }
    }

    private function record(
        string $userId,
        string $type,
        float $delta,
        string $ctxType,
        string $ctxId,
    ): void {
        ReputationEvent::create([
            'user_id'      => $userId,
            'event_type'   => $type,
            'delta'        => $delta,
            'context_type' => $ctxType,
            'context_id'   => $ctxId,
        ]);
    }
}
