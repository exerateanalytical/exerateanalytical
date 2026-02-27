<?php

namespace App\Observers;

use App\Models\ReputationEvent;
use App\Services\Trust\BadgeService;
use App\Services\Trust\ReputationService;

class ReputationEventObserver
{
    public function __construct(
        private readonly ReputationService $reputationService,
        private readonly BadgeService $badgeService,
    ) {}

    /**
     * After every new ReputationEvent, recalculate the user's score + tier,
     * then evaluate badge eligibility.
     */
    public function created(ReputationEvent $event): void
    {
        $user = $event->user;

        $this->reputationService->recalculate($user);

        // Refresh to pick up the just-persisted tier before badge evaluation
        $user->refresh();

        $this->badgeService->recalculate($user);
    }
}
