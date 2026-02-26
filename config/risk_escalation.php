<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Escalation Thresholds (minutes)
    |--------------------------------------------------------------------------
    | Minutes an active, unacknowledged alert must remain at a given severity
    | before being escalated to the next level.
    | null = never escalate (severity is already the ceiling).
    */
    'thresholds' => [
        'medium'   => 180,
        'high'     => 60,
        'critical' => null,
    ],

];
