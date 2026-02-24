<?php

use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\InstitutionController;
use App\Http\Controllers\Api\V1\RegionController;
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
        Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('/countries/{country}', [CountryController::class, 'show'])->name('countries.show');
        Route::get('/countries/{country}/regions', [RegionController::class, 'index'])->name('countries.regions.index');
        Route::get('/countries/{country}/regions/{region}', [RegionController::class, 'show'])->name('countries.regions.show');
        Route::get('/countries/{country}/institutions', [InstitutionController::class, 'index'])->name('countries.institutions.index');
        Route::get('/countries/{country}/institutions/{institution}', [InstitutionController::class, 'show'])->name('countries.institutions.show');
    });

    // ─── Authenticated Admin Routes ────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Authenticated user info
        Route::get('/user', fn (Request $request) => $request->user())->name('user');

        // Country management (SuperAdmin only via Policy)
        Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('/countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('/countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        // Region management (SuperAdmin / CountryAdmin via Policy)
        Route::post('/countries/{country}/regions', [RegionController::class, 'store'])->name('countries.regions.store');
        Route::put('/countries/{country}/regions/{region}', [RegionController::class, 'update'])->name('countries.regions.update');
        Route::delete('/countries/{country}/regions/{region}', [RegionController::class, 'destroy'])->name('countries.regions.destroy');

        // Institution management (SuperAdmin / CountryAdmin via Policy)
        Route::post('/countries/{country}/institutions', [InstitutionController::class, 'store'])->name('countries.institutions.store');
        Route::put('/countries/{country}/institutions/{institution}', [InstitutionController::class, 'update'])->name('countries.institutions.update');
        Route::delete('/countries/{country}/institutions/{institution}', [InstitutionController::class, 'destroy'])->name('countries.institutions.destroy');
    });
});
