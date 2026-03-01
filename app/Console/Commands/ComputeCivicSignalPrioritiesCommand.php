<?php

namespace App\Console\Commands;

use App\Services\Civic\SignalPriorityService;
use Illuminate\Console\Command;
use Throwable;

class ComputeCivicSignalPrioritiesCommand extends Command
{
    protected $signature = 'civic:compute-signal-priorities';

    protected $description = 'CT-11 — Compute civic signal priority scores for active polls, petitions, and policies';

    public function __construct(
        private readonly SignalPriorityService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->service->compute();
            $this->info('Civic signal priorities computed successfully.');
        } catch (Throwable $e) {
            $this->error("Civic signal priority computation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
