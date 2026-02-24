<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;

class InstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Institution $institution): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }

    public function update(User $user, Institution $institution): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }

    public function delete(User $user, Institution $institution): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }
}
