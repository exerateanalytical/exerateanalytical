<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Inertia\Inertia;
use Inertia\Response;

class TransparencyWebController extends Controller
{
    public function show(Country $country): Response
    {
        try {
            $methodology = \App\Models\MethodologyVersion::where('country_id', $country->id)
                ->where('is_active', true)
                ->first();
        } catch (\Throwable $e) {
            $methodology = null;
        }

        try {
            $pillars = \App\Models\Pillar::where('country_id', $country->id)
                ->orderBy('name')
                ->get(['id', 'name', 'weight', 'description']);
        } catch (\Throwable $e) {
            $pillars = collect();
        }

        try {
            $indicators = \App\Models\Indicator::where('country_id', $country->id)
                ->orderBy('name')
                ->get(['id', 'name', 'pillar_id', 'weight', 'normalization_method', 'description']);
        } catch (\Throwable $e) {
            $indicators = collect();
        }

        try {
            $reliability = \App\Models\IndicatorReliabilityScore::where('country_id', $country->id)
                ->latest()
                ->limit(20)
                ->get(['indicator_id', 'score', 'assessed_at', 'notes']);
        } catch (\Throwable $e) {
            $reliability = collect();
        }

        try {
            $publications = \App\Models\PublicationArchive::where('country_id', $country->id)
                ->orderByDesc('publication_year')
                ->limit(10)
                ->get(['id', 'title', 'publication_year', 'document_type', 'url']);
        } catch (\Throwable $e) {
            $publications = collect();
        }

        try {
            $disruptions = \App\Models\DataAccessDisruption::where('country_id', $country->id)
                ->orderByDesc('started_at')
                ->limit(5)
                ->get(['id', 'indicator_id', 'disruption_type', 'severity', 'started_at', 'resolved_at', 'description']);
        } catch (\Throwable $e) {
            $disruptions = collect();
        }

        return Inertia::render('Transparency/Show', compact(
            'country',
            'methodology',
            'pillars',
            'indicators',
            'reliability',
            'publications',
            'disruptions'
        ));
    }
}
