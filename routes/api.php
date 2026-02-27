<?php

use App\Http\Controllers\Api\V1\AccountabilityController;
use App\Http\Controllers\Api\V1\AlertSubscriptionController;
use App\Http\Controllers\Api\V1\CivicController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\DevelopmentController;
use App\Http\Controllers\Api\V1\ExecutiveAlertAnalyticsController;
use App\Http\Controllers\Api\V1\ExecutiveAlertController;
use App\Http\Controllers\Api\V1\ExecutiveBriefController;
use App\Http\Controllers\Api\V1\ExecutiveGovernanceMetricsController;
use App\Http\Controllers\Api\V1\ExecutiveNationalController;
use App\Http\Controllers\Api\V1\ExecutiveNetworkRankingController;
use App\Http\Controllers\Api\V1\ExecutiveRiskDashboardController;
use App\Http\Controllers\Api\V1\ExposureMatrixController;
use App\Http\Controllers\Api\V1\FederationController;
use App\Http\Controllers\Api\V1\FiscalController;
use App\Http\Controllers\Api\V1\GovernanceController;
use App\Http\Controllers\Api\V1\InstitutionController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\RegionalRiskIntelligenceController;
use App\Http\Controllers\Api\V1\RiskContagionController;
use App\Http\Controllers\Api\V1\RiskIntelligenceController;
use App\Http\Controllers\Api\V1\TransparencyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Exerate Analytical
|--------------------------------------------------------------------------
| Versioned under api/v1
| Rate-limited: 60 requests per minute
| Authentication: Laravel Sanctum tokens
*/

// Public informational endpoint
Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ─── Public Data Routes ────────────────────────────────────────────────
    Route::middleware(['throttle:60,1'])->group(function () {
        // Countries, Regions, Institutions
        Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('/countries/{country}', [CountryController::class, 'show'])->name('countries.show');
        Route::get('/countries/{country}/regions', [RegionController::class, 'index'])->name('countries.regions.index');
        Route::get('/countries/{country}/regions/{region}', [RegionController::class, 'show'])->name('countries.regions.show');
        Route::get('/countries/{country}/institutions', [InstitutionController::class, 'index'])->name('countries.institutions.index');
        Route::get('/countries/{country}/institutions/{institution}', [InstitutionController::class, 'show'])->name('countries.institutions.show');

        // Governance
        Route::get('/governance/{country}/{year}', [GovernanceController::class, 'show'])->whereNumber('year')->name('governance.show');
        Route::get('/governance/{country}/{region}/{year}', [GovernanceController::class, 'showRegional'])->whereNumber('year')->name('governance.regional');
        Route::get('/governance/{country}/trend', [GovernanceController::class, 'trend'])->name('governance.trend');

        // Fiscal
        Route::get('/fiscal/debt/{country}/{year}', [FiscalController::class, 'debt'])->whereNumber('year')->name('fiscal.debt');
        Route::get('/fiscal/budget/{country}/{year}', [FiscalController::class, 'budget'])->whereNumber('year')->name('fiscal.budget');
        Route::get('/fiscal/revenue/{country}/{year}', [FiscalController::class, 'revenue'])->whereNumber('year')->name('fiscal.revenue');
        Route::get('/fiscal/risk/{country}/{year}', [FiscalController::class, 'risk'])->whereNumber('year')->name('fiscal.risk');

        // Development
        Route::get('/development/{country}/{year}', [DevelopmentController::class, 'show'])->name('development.show');
        Route::get('/development/{country}/{region}/{year}', [DevelopmentController::class, 'showRegional'])->name('development.regional');
        Route::get('/development/{country}/trend', [DevelopmentController::class, 'trend'])->name('development.trend');

        // Accountability
        Route::get('/accountability/{country}/{year}', [AccountabilityController::class, 'index'])->whereNumber('year')->name('accountability.index');
        Route::get('/accountability/{country}/{region}/{year}', [AccountabilityController::class, 'showRegional'])->whereNumber('year')->name('accountability.regional');
        Route::get('/accountability/{country}/institution/{institution}', [AccountabilityController::class, 'showInstitution'])->name('accountability.institution');

        // Civic
        Route::get('/civic/approval/{country}/{year}', [CivicController::class, 'approval'])->name('civic.approval');
        Route::get('/civic/approval/{country}/{region}/{year}', [CivicController::class, 'approvalRegional'])->name('civic.approval.regional');
        Route::get('/civic/representation/{country}/{year}', [CivicController::class, 'representation'])->name('civic.representation');
        Route::get('/civic/petitions/{country}', [CivicController::class, 'petitions'])->name('civic.petitions');

        // Risk Intelligence
        Route::get('/risk-intelligence/{country}', [RiskIntelligenceController::class, 'show'])->name('risk.intelligence');

        // Regional Risk Intelligence
        Route::get('/risk/regional/{country}', [RegionalRiskIntelligenceController::class, 'ranking'])->name('risk.regional.ranking');
        Route::get('/risk/regional/{country}/{region}', [RegionalRiskIntelligenceController::class, 'detail'])->name('risk.regional.detail');

        // Executive National
        Route::get('/executive/national/{country}', [ExecutiveNationalController::class, 'show'])->name('executive.national');
        Route::get('/executive/network-ranking', [ExecutiveNetworkRankingController::class, 'index'])->name('executive.network-ranking');
        Route::get('/executive/brief/{country}', [ExecutiveBriefController::class, 'show'])->name('executive.brief');
        Route::get('/executive/alerts/{country}', [ExecutiveAlertController::class, 'show'])->name('executive.alerts');
        Route::get('/executive/alerts/{country}/metrics', [ExecutiveAlertAnalyticsController::class, 'metrics'])->name('executive.alerts.metrics');
        Route::get('/executive/governance/{country}', [ExecutiveGovernanceMetricsController::class, 'metrics'])->name('executive.governance.metrics');

        // Federation aggregated snapshots
        Route::get('/federation/global', [FederationController::class, 'global'])->name('federation.global');
        Route::get('/federation/regions', [FederationController::class, 'regions'])->name('federation.regions');

        // Executive Risk Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/risk-ranking', [ExecutiveRiskDashboardController::class, 'riskRanking'])->name('risk-ranking');
            Route::get('/risk-drivers/{country}', [ExecutiveRiskDashboardController::class, 'riskDrivers'])->name('risk-drivers');
            Route::get('/risk-history/{country}', [ExecutiveRiskDashboardController::class, 'riskHistory'])->name('risk-history');
            Route::get('/alert-watchlist', [ExecutiveRiskDashboardController::class, 'alertWatchlist'])->name('alert-watchlist');
        });

        // Transparency & Methodology
        Route::get('/methodology/{country}', [TransparencyController::class, 'methodology'])->name('transparency.methodology');
        Route::get('/indicators/{country}', [TransparencyController::class, 'indicators'])->name('transparency.indicators');
        Route::get('/reliability/{country}', [TransparencyController::class, 'reliability'])->name('transparency.reliability');
        Route::get('/publications/{country}/{year}', [TransparencyController::class, 'publications'])->name('transparency.publications');
        Route::get('/open-data/{country}/{module}/{year}', [TransparencyController::class, 'openData'])->name('transparency.open-data');
        Route::get('/disruptions/{country}', [TransparencyController::class, 'disruptions'])->name('transparency.disruptions');
    });

    // ─── Authenticated Admin Routes ────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Authenticated user info
        Route::get('/user', fn (Request $request) => $request->user())->name('user');

        // Country management (SuperAdmin only via Policy)
        Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('/countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('/countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        // Region management
        Route::post('/countries/{country}/regions', [RegionController::class, 'store'])->name('countries.regions.store');
        Route::put('/countries/{country}/regions/{region}', [RegionController::class, 'update'])->name('countries.regions.update');
        Route::delete('/countries/{country}/regions/{region}', [RegionController::class, 'destroy'])->name('countries.regions.destroy');

        // Institution management
        Route::post('/countries/{country}/institutions', [InstitutionController::class, 'store'])->name('countries.institutions.store');
        Route::put('/countries/{country}/institutions/{institution}', [InstitutionController::class, 'update'])->name('countries.institutions.update');
        Route::delete('/countries/{country}/institutions/{institution}', [InstitutionController::class, 'destroy'])->name('countries.institutions.destroy');

        // Development data ingestion and recalculation
        Route::post('/development', [DevelopmentController::class, 'store'])->name('development.store');
        Route::post('/development/{country}/{year}/recalculate', [DevelopmentController::class, 'recalculate'])->name('development.recalculate');

        // Governance management
        Route::post('/governance/{country}/{year}/recalculate', [GovernanceController::class, 'recalculate'])->name('governance.recalculate');
        Route::get('/governance/{country}/{year}/sensitivity', [GovernanceController::class, 'sensitivity'])->name('governance.sensitivity');

        // Accountability management
        Route::post('/accountability/{country}/{entity}/recalculate', [AccountabilityController::class, 'recalculate'])->whereUuid('entity')->name('accountability.recalculate');

        // Civic petitions (public submission allowed without auth, but admin review requires auth)
        Route::post('/civic/petitions', [\App\Http\Controllers\Api\V1\CivicController::class, 'storePetition'])->name('civic.petitions.store');

        // Exposure Matrix management (SuperAdmin only via FormRequest)
        Route::post('/exposure-matrix', [ExposureMatrixController::class, 'store'])->name('exposure-matrix.store');
        Route::put('/exposure-matrix/{id}/activate', [ExposureMatrixController::class, 'activate'])->whereUuid('id')->name('exposure-matrix.activate');
        Route::get('/exposure-matrix/active', [ExposureMatrixController::class, 'active'])->name('exposure-matrix.active');

        // Alert acknowledgement
        Route::put('/executive/alerts/{id}/acknowledge', [ExecutiveAlertController::class, 'acknowledge'])->whereUuid('id')->name('executive.alerts.acknowledge');

        // Alert Subscription management (SuperAdmin only)
        Route::apiResource('alert-subscriptions', AlertSubscriptionController::class)
            ->whereUuid('alert_subscription');
    });

    // ─── Internal SuperAdmin Routes ────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:60,1'])
        ->prefix('internal')
        ->name('internal.')
        ->group(function () {
            Route::post('/risk/contagion', [RiskContagionController::class, 'execute'])
                ->name('risk.contagion');
        });
});
