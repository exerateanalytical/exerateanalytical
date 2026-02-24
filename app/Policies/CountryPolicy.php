<?php

namespace App\Policies;

use App\Models\Country;
use App\Models\User;

class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Country $country): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    public function update(User $user, Country $country): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    public function delete(User $user, Country $country): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    public function restore(User $user, Country $country): bool
    {
        return $user->hasRole('SuperAdmin');
    }

    public function forceDelete(User $user, Country $country): bool
    {
        return false;
    }
}
