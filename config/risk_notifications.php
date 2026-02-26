<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retriggered-Event Suppression Window
    |--------------------------------------------------------------------------
    | Suppresses duplicate notifications for retriggered events on the same
    | alert within this many minutes. Channel configuration is now managed
    | per-subscription via the risk_alert_subscriptions table.
    */
    'suppression_minutes' => (int) env('RISK_NOTIFICATION_SUPPRESSION_MINUTES', 10),

];
