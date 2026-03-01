<?php

namespace App\Console\Commands;

use App\Services\Civic\SignalExplanationService;
use Illuminate\Console\Command;
use Throwable;

class GenerateCivicSignalExplanationsCommand extends Command
{
    protected $signature = 'civic:generate-signal-explanations';

    protected $description = 'CT-12 — Generate causal explanations for the latest civic signal priority batch';

    public function __construct(
        private readonly SignalExplanationService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->service->generateForBatch();
            $this->info('Civic signal explanations generated successfully.');
        } catch (Throwable $e) {
            $this->error("Signal explanation generation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
