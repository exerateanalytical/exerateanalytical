<?php

namespace App\Services\Civic;

use App\Models\Petition;
use Illuminate\Support\Facades\Log;

class PetitionModerationService
{
    private array $prohibitedPatterns = [
        '/\b(kill|murder|assassinate|bomb|attack)\b/i',
        '/\b(hate|racist|sexist|bigot)\b/i',
    ];

    public function moderate(Petition $petition): array
    {
        $issues = [];

        foreach ($this->prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $petition->title) || preg_match($pattern, $petition->description)) {
                $issues[] = 'Prohibited language detected.';
                break;
            }
        }

        if ($this->isDuplicate($petition)) {
            $issues[] = 'Duplicate petition detected.';
        }

        return $issues;
    }

    public function isDuplicate(Petition $petition): bool
    {
        $hash = md5(strtolower(trim($petition->title)) . '|' . strtolower(trim($petition->description)));

        return Petition::where('country_id', $petition->country_id)
            ->where('content_hash', $hash)
            ->where('id', '!=', $petition->id)
            ->exists();
    }

    public function generateHash(string $title, string $description): string
    {
        return md5(strtolower(trim($title)) . '|' . strtolower(trim($description)));
    }

    public function approve(Petition $petition, string $reviewerId): Petition
    {
        $petition->status = 'approved';
        $petition->save();

        Log::info('Petition approved', ['petition_id' => $petition->id, 'reviewer' => $reviewerId]);

        return $petition;
    }

    public function reject(Petition $petition, string $reviewerId, string $reason = ''): Petition
    {
        $petition->status = 'rejected';
        $petition->save();

        Log::info('Petition rejected', [
            'petition_id' => $petition->id,
            'reviewer' => $reviewerId,
            'reason' => $reason,
        ]);

        return $petition;
    }
}
