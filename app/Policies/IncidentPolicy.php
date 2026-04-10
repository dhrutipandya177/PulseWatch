<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\Incident;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return tenant() !== null;
    }

    public function view(User $user, Incident $incident): bool
    {
        return tenant() !== null;
    }

    public function create(User $user): bool
    {
        return tenant() !== null;
    }

    public function update(User $user, Incident $incident): bool
    {
        return tenant() !== null;
    }

    public function delete(User $user, Incident $incident): bool
    {
        return tenant() !== null;
    }
}
