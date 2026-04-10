<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\Monitor;

class MonitorPolicy
{
    public function viewAny(User $user): bool
    {
        return tenant() !== null;
    }

    public function view(User $user, Monitor $monitor): bool
    {
        return tenant() !== null;
    }

    public function create(User $user): bool
    {
        if (!tenant()) {
            return false;
        }

        $currentCount = Monitor::count();
        $limit = $user->plan?->max_monitors ?? 5;

        return $currentCount < $limit;
    }

    public function update(User $user, Monitor $monitor): bool
    {
        return tenant() !== null;
    }

    public function delete(User $user, Monitor $monitor): bool
    {
        return tenant() !== null;
    }
}
