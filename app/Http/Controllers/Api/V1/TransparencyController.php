<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DataAccessDisruption;
use App\Models\IndicatorReliabilityScore;
use App\Models\MethodologyVersion;
use App\Models\Pillar;
use App\Models\PublicationArchive;
use App\Services\Transparency\ImmutableArchiveService;
use App\Services\Transparency\MethodologyGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TransparencyController extends Controller
{
    public function __construct(
        private readonly MethodologyGovernanceService $methodologyService,
        private readonly ImmutableArchiveService $archiveService,
    ) {}

    public function methodology(string $countryId): JsonResponse
    {
        $version = Cache::remember("methodology:{$countryId}", 10800, function () use ($countryId) {
            return $this->methodologyService->getActiveVersion($countryId);
        });

        $pillars = Pillar::where('country_id', $countryId)->active()->with('indicators')->get();

        return response()->json([
            'active_version' => $version,
            'pillars' => $pillars,
        ]);
    }

    public function indicators(string $countryId): JsonResponse
    {
        $indicators = \App\Models\Indicator::where('country_id', $countryId)
            ->active()
            ->with(['pillar', 'reliabilityScore'])
            ->get();

        return response()->json(['data' => $indicators]);
    }

    public function reliability(string $countryId): JsonResponse
    {
        $scores = IndicatorReliabilityScore::whereHas('indicator', fn ($q) => $q->where('country_id', $countryId))
            ->with('indicator')
            ->get();

        return response()->json(['data' => $scores]);
    }

    public function publications(string $countryId, int $year): JsonResponse
    {
        $publications = PublicationArchive::where('country_id', $countryId)
            ->where('year', $year)
            ->get();

        return response()->json(['data' => $publications]);
    }

    public function openData(string $countryId, string $module, int $year): JsonResponse
    {
        $data = match ($module) {
            'governance' => \App\Models\GovernanceScore::where('country_id', $countryId)->where('year', $year)->get(),
            'fiscal' => \App\Models\NationalDebtRecord::where('country_id', $countryId)->where('year', $year)->get(),
            'development' => \App\Models\ServiceAccessRecord::where('country_id', $countryId)->where('year', $year)->whereNull('region_id')->get(),
            default => collect([]),
        };

        return response()->json(['data' => $data, 'module' => $module, 'year' => $year]);
    }

    public function disruptions(string $countryId): JsonResponse
    {
        $disruptions = DataAccessDisruption::where('country_id', $countryId)
            ->orderBy('flagged_at', 'desc')
            ->get();

        return response()->json(['data' => $disruptions]);
    }
}
