<?php

namespace App\Services\Fiscal;

use App\Models\FiscalRiskSignal;
use Illuminate\Database\Eloquent\Collection;

class FiscalRiskSignalService
{
    public function getSignals(string $countryId, int $year): Collection
    {
        return FiscalRiskSignal::where('country_id', $countryId)
            ->where('year', $year)
            ->orderBy('triggered_at', 'desc')
            ->get();
    }
}
