<?php

namespace App\Policies;

use App\Models\User;

class DevelopmentPolicy
{
    public function store(User $user): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin', 'DataAnalyst']);
    }

    public function recalculate(User $user): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }
}
