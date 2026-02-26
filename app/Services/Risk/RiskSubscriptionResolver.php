<?php

namespace App\Services\Risk;

use App\Models\RiskAlertEvent;
use App\Models\RiskAlertSubscription;
use Illuminate\Support\Collection;

class RiskSubscriptionResolver
{
    /**
     * Resolve all active subscriptions that match the given event.
     *
     * Matching rules (all must hold):
     *   country_id  is null OR equals event.country_id
     *   alert_type  is null OR equals event.type
     *   severity    is null OR equals event.severity
     *   active      = true
     *
     * Single query, no N+1.
     */
    public function resolve(RiskAlertEvent $event): Collection
    {
        return RiskAlertSubscription::where('active', true)
            ->where(function ($q) use ($event) {
                $q->whereNull('country_id')
                  ->orWhere('country_id', $event->country_id);
            })
            ->where(function ($q) use ($event) {
                $q->whereNull('alert_type')
                  ->orWhere('alert_type', $event->type);
            })
            ->where(function ($q) use ($event) {
                $q->whereNull('severity')
                  ->orWhere('severity', $event->severity);
            })
            ->get();
    }
}
