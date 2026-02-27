<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public trust profile for a user.
 * Exposes reputation_tier and badges only — raw reputation_score is NOT included.
 */
class UserTrustResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'reputation_tier' => $this->reputation_tier ?? 'Citizen',
            'badges'          => $this->badges->map(fn ($badge) => [
                'code'       => $badge->badge_code,
                'awarded_at' => $badge->awarded_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
