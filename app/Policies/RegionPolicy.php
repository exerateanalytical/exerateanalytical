<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Region $region): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }

    public function update(User $user, Region $region): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }

    public function delete(User $user, Region $region): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }
}
