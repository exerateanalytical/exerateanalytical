<?php

use App\Events\GovernanceScoreRecalculated;
use App\Jobs\GovernanceRiskEvaluationJob;
use App\Jobs\RecalculateGovernanceScoreJob;
use App\Models\Country;
use App\Models\GovernanceScore;
use App\Models\RiskSignal;
use App\Services\Governance\GovernanceIndexService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

// Ensure Mockery mocks and spies are torn down between tests
afterEach(fn () => Mockery::close());

// ─────────────────────────────────────────────────────────────────────────────
// RecalculateGovernanceScoreJob
// ─────────────────────────────────────────────────────────────────────────────

it('RecalculateGovernanceScoreJob calls calculateCompositeScore on the service with correct arguments', function () {
    $country = Country::factory()->create();

    $scoreRecord = GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 75.00,
    ]);

    $mockService = Mockery::mock(GovernanceIndexService::class);
    $mockService->shouldReceive('calculateCompositeScore')
        ->once()
        ->with($country->id, null, 2024)
        ->andReturn($scoreRecord);

    Event::fake();

    (new RecalculateGovernanceScoreJob($country->id, 2024))->handle($mockService);
});

it('RecalculateGovernanceScoreJob dispatches GovernanceScoreRecalculated with the country, year, and composite score', function () {
    $country = Country::factory()->create();

    $scoreRecord = GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 75.00,
    ]);

    $mockService = Mockery::mock(GovernanceIndexService::class);
    $mockService->shouldReceive('calculateCompositeScore')
        ->andReturn($scoreRecord);

    Event::fake();

    (new RecalculateGovernanceScoreJob($country->id, 2024))->handle($mockService);

    Event::assertDispatched(
        GovernanceScoreRecalculated::class,
        function (GovernanceScoreRecalculated $event) use ($country) {
            return $event->country->id === $country->id
                && $event->year === 2024
                && $event->score === 75.0;
        }
    );
});

it('RecalculateGovernanceScoreJob does not directly dispatch GovernanceRiskEvaluationJob', function () {
    $country = Country::factory()->create();

    $scoreRecord = GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 75.00,
    ]);

    $mockService = Mockery::mock(GovernanceIndexService::class);
    $mockService->shouldReceive('calculateCompositeScore')
        ->andReturn($scoreRecord);

    Queue::fake();
    Event::fake();

    (new RecalculateGovernanceScoreJob($country->id, 2024))->handle($mockService);

    Queue::assertNotPushed(GovernanceRiskEvaluationJob::class);
});

it('RecalculateGovernanceScoreJob failed() writes a critical log entry with country, year, and error message', function () {
    $country   = Country::factory()->create();
    $exception = new RuntimeException('Simulated DB failure');

    Log::spy();

    (new RecalculateGovernanceScoreJob($country->id, 2024))->failed($exception);

    Log::shouldHaveReceived('critical')
        ->once()
        ->withArgs(fn ($message, $context) =>
            $message === 'RecalculateGovernanceScoreJob failed permanently'
            && $context['country_id'] === $country->id
            && $context['year'] === 2024
            && $context['error'] === 'Simulated DB failure'
        );
});

// ─────────────────────────────────────────────────────────────────────────────
// GovernanceRiskEvaluationJob
// ─────────────────────────────────────────────────────────────────────────────

it('GovernanceRiskEvaluationJob creates a RiskSignal when the score drops more than 10%', function () {
    $country = Country::factory()->create();

    // drop = (80 - 68) / 80 * 100 = 15% > 10 → signal created
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2023,
        'composite_score' => 80.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 68.00,
    ]);

    (new GovernanceRiskEvaluationJob($country->id, 2024))->handle();

    expect(RiskSignal::where('country_id', $country->id)->count())->toBe(1);
});

it('GovernanceRiskEvaluationJob sets severity to high when the drop is between 10% and 20%', function () {
    $country = Country::factory()->create();

    // drop = (80 - 68) / 80 * 100 = 15% → severity 'high'
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2023,
        'composite_score' => 80.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 68.00,
    ]);

    (new GovernanceRiskEvaluationJob($country->id, 2024))->handle();

    $signal = RiskSignal::where('country_id', $country->id)->first();

    expect($signal->severity)->toBe('high');
    expect($signal->signal_type)->toBe('governance_score_drop');
});

it('GovernanceRiskEvaluationJob sets severity to critical when the drop exceeds 20%', function () {
    $country = Country::factory()->create();

    // drop = (80 - 56) / 80 * 100 = 30% → severity 'critical'
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2023,
        'composite_score' => 80.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 56.00,
    ]);

    (new GovernanceRiskEvaluationJob($country->id, 2024))->handle();

    $signal = RiskSignal::where('country_id', $country->id)->first();

    expect($signal->severity)->toBe('critical');
});

it('GovernanceRiskEvaluationJob persists all five structured metadata fields on the signal', function () {
    $country = Country::factory()->create();

    // drop = (80 - 68) / 80 * 100 = 15.0; round(15.0, 2) stores as JSON integer 15
    // (float) cast in assertions handles int→float coercion from JSONB retrieval
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2023,
        'composite_score' => 80.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 68.00,
    ]);

    (new GovernanceRiskEvaluationJob($country->id, 2024))->handle();

    $signal = RiskSignal::where('country_id', $country->id)->first();
    $meta   = $signal->metadata;

    expect($meta)->toHaveKeys(['previous_year', 'current_year', 'previous_score', 'current_score', 'drop_percent']);
    expect($meta['previous_year'])->toBe(2023);
    expect($meta['current_year'])->toBe(2024);
    expect((float) $meta['previous_score'])->toBe(80.0);
    expect((float) $meta['current_score'])->toBe(68.0);
    expect((float) $meta['drop_percent'])->toBe(15.0);
});

it('GovernanceRiskEvaluationJob does not create a RiskSignal when the drop is exactly 10%', function () {
    $country = Country::factory()->create();

    // drop = (80 - 72) / 80 * 100 = 10.0% — condition is strictly > 10, so no signal
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2023,
        'composite_score' => 80.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 72.00,
    ]);

    (new GovernanceRiskEvaluationJob($country->id, 2024))->handle();

    expect(RiskSignal::where('country_id', $country->id)->exists())->toBeFalse();
});

it('GovernanceRiskEvaluationJob escalates the signal when the score has declined for 2 consecutive years', function () {
    $country = Country::factory()->create();

    // 2022→2023: 90→80 (first decline), 2023→2024: 80→68 (second decline, 15% drop > 10 → signal + escalate)
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2022,
        'composite_score' => 90.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2023,
        'composite_score' => 80.00,
    ]);
    GovernanceScore::factory()->create([
        'country_id'      => $country->id,
        'year'            => 2024,
        'composite_score' => 68.00,
    ]);

    (new GovernanceRiskEvaluationJob($country->id, 2024))->handle();

    $signal = RiskSignal::where('country_id', $country->id)->first();

    expect($signal->is_escalated)->toBeTrue();
});
