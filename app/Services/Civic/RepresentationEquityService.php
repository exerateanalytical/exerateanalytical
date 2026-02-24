<?php

namespace App\Services\Civic;

use App\Models\RepresentationRecord;
use Illuminate\Support\Facades\Log;

class RepresentationEquityService
{
    public function calculateEquityIndex(string $recordId): float
    {
        $record = RepresentationRecord::findOrFail($recordId);

        $distributions = array_filter([
            $record->gender_distribution,
            $record->age_distribution,
            $record->professional_background_distribution,
        ]);

        if (empty($distributions)) {
            Log::warning('No distribution data for equity index', ['record_id' => $recordId]);
            return 0.0;
        }

        $deviationScores = [];
        foreach ($distributions as $distribution) {
            if (!is_array($distribution) || empty($distribution)) continue;

            $values = array_values($distribution);
            $n = count($values);
            $expected = 100 / $n;
            $totalDeviation = array_sum(array_map(fn ($v) => abs((float)$v - $expected), $values));
            $maxDeviation = $expected * $n;
            $deviationScores[] = $maxDeviation > 0 ? ($totalDeviation / $maxDeviation) : 0;
        }

        if (empty($deviationScores)) return 0.0;

        $avgDeviation = array_sum($deviationScores) / count($deviationScores);
        $equityScore = round((1 - $avgDeviation) * 100, 2);

        $record->equity_index_score = max(0, min(100, $equityScore));
        $record->save();

        return $record->equity_index_score;
    }
}
