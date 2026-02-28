<?php

namespace App\Console\Commands;

use App\Services\Governance\InstitutionalInfluenceService;
use Illuminate\Console\Command;
use Throwable;

class ComputeInstitutionalInfluencesCommand extends Command
{
    protected $signature = 'governance:compute-institutional-influences';

    protected $description = 'Compute institutional and actor influence scores from the last 30 days of civic activity';

    public function __construct(
        private readonly InstitutionalInfluenceService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $actors = $this->service->compute();
            $this->info("Institutional Influence: {$actors->count()} actor(s) computed.");

            foreach ($actors as $actor) {
                $region = $actor->region?->code ?? 'global';
                $name   = $actor->actor?->name ?? $actor->actor_id;
                $this->line(sprintf(
                    '  [%s] %s — %s (score %.1f, trust Δ %+.2f)',
                    $region,
                    $name,
                    $actor->influence_category,
                    $actor->influence_score,
                    $actor->trust_delta,
                ));
            }
        } catch (Throwable $e) {
            $this->error("Institutional influence computation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
