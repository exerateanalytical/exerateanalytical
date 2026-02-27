<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\CivicFeedWebController;
use App\Http\Controllers\Web\CountriesWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExecutiveDashboardWebController;
use App\Http\Controllers\Web\FederationWebController;
use App\Http\Controllers\Web\PetitionWebController;
use App\Http\Controllers\Web\PolicyWebController;
use App\Http\Controllers\Web\PollWebController;
use App\Http\Controllers\Web\RiskDashboardWebController;
use App\Http\Controllers\Web\TransparencyWebController;
use App\Http\Controllers\Web\TrustWebController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Welcome ──────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'      => Route::has('login'),
        'canRegister'   => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'    => PHP_VERSION,
    ]);
});

// ── Authenticated core (Jetstream — unchanged) ────────────────────────────────
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// ── Civic public routes (guests + auth users) ─────────────────────────────────
// NOTE: /create routes are declared BEFORE /{id} routes to prevent route shadowing.
Route::prefix('civic')->name('civic.')->group(function () {

    // Feed
    Route::get('/feed', [CivicFeedWebController::class, 'index'])->name('feed');

    // Polls – list + detail (public)
    Route::get('/polls',       [PollWebController::class, 'index'])->name('polls.index');

    // Polls – create (auth required)
    Route::get('/polls/create', [PollWebController::class, 'create'])
        ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
        ->name('polls.create');

    // Polls – detail (public, after create to prevent shadowing)
    Route::get('/polls/{poll}', [PollWebController::class, 'show'])
        ->whereUuid('poll')
        ->name('polls.show');

    // Petitions – list (public)
    Route::get('/petitions', [PetitionWebController::class, 'index'])->name('petitions.index');

    // Petitions – create (auth required)
    Route::get('/petitions/create', [PetitionWebController::class, 'create'])
        ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
        ->name('petitions.create');

    // Petitions – detail (public)
    Route::get('/petitions/{petition}', [PetitionWebController::class, 'show'])
        ->whereUuid('petition')
        ->name('petitions.show');

    // Policies – list (public)
    Route::get('/policies', [PolicyWebController::class, 'index'])->name('policies.index');

    // Policies – create (auth required)
    Route::get('/policies/create', [PolicyWebController::class, 'create'])
        ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
        ->name('policies.create');

    // Policies – detail (public)
    Route::get('/policies/{policy}', [PolicyWebController::class, 'show'])
        ->whereUuid('policy')
        ->name('policies.show');
});

// ── Trust profiles (public) ───────────────────────────────────────────────────
Route::get('/trust/{user}', [TrustWebController::class, 'show'])
    ->whereUuid('user')
    ->name('trust.profile');

// ── Countries (public) ────────────────────────────────────────────────────────
Route::prefix('countries')->name('countries.')->group(function () {
    Route::get('/',          [CountriesWebController::class, 'index'])->name('index');
    Route::get('/{country}', [CountriesWebController::class, 'show'])->whereUuid('country')->name('show');
});

// ── Risk Intelligence (public) ────────────────────────────────────────────────
Route::prefix('risk')->name('risk.')->group(function () {
    Route::get('/',          [RiskDashboardWebController::class, 'index'])->name('index');
    Route::get('/{country}', [RiskDashboardWebController::class, 'show'])->whereUuid('country')->name('show');
});

// ── Executive Dashboard (public) ─────────────────────────────────────────────
Route::prefix('executive')->name('executive.')->group(function () {
    Route::get('/',          [ExecutiveDashboardWebController::class, 'index'])->name('index');
    Route::get('/{country}', [ExecutiveDashboardWebController::class, 'show'])->whereUuid('country')->name('show');
});

// ── Federation Overview (public) ─────────────────────────────────────────────
Route::get('/federation', [FederationWebController::class, 'index'])->name('federation.index');

// ── Transparency (public) ─────────────────────────────────────────────────────
Route::get('/transparency/{country}', [TransparencyWebController::class, 'show'])
    ->whereUuid('country')
    ->name('transparency.show');

// ── Admin Panel (auth + SuperAdmin) ──────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/',                             [AdminWebController::class, 'dashboard'])->name('dashboard');
    Route::get('/countries',                    [AdminWebController::class, 'countries'])->name('countries.index');
    Route::get('/countries/create',             [AdminWebController::class, 'createCountry'])->name('countries.create');
    Route::get('/countries/{country}/edit',     [AdminWebController::class, 'editCountry'])->whereUuid('country')->name('countries.edit');
    Route::get('/countries/{country}/regions',  [AdminWebController::class, 'countryRegions'])->whereUuid('country')->name('countries.regions');
    Route::get('/exposure-matrix',              [AdminWebController::class, 'exposureMatrix'])->name('exposure-matrix');
    Route::get('/alert-subscriptions',          [AdminWebController::class, 'alertSubscriptions'])->name('alert-subscriptions');
    Route::get('/risk-contagion',               [AdminWebController::class, 'riskContagion'])->name('risk-contagion');
});
