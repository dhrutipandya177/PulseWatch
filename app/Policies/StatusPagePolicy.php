<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\StatusPage;

class StatusPagePolicy
{
    public function viewAny(User $user): bool
    {
        return tenant() !== null;
    }

    public function view(User $user, StatusPage $statusPage): bool
    {
        return tenant() !== null;
    }

    public function create(User $user): bool
    {
        if (!tenant()) {
            return false;
        }

        return $user->plan?->has_status_page ?? false;
    }

    public function update(User $user, StatusPage $statusPage): bool
    {
        return tenant() !== null;
    }

    public function delete(User $user, StatusPage $statusPage): bool
    {
        return tenant() !== null;
    }
}
