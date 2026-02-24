<?php

namespace App\Events;

use App\Models\Country;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CountryUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Country $country, public readonly array $oldData) {}
}
