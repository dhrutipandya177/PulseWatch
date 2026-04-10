<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\Component;

class ComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return tenant() !== null;
    }

    public function view(User $user, Component $component): bool
    {
        return tenant() !== null;
    }

    public function create(User $user): bool
    {
        return tenant() !== null;
    }

    public function update(User $user, Component $component): bool
    {
        return tenant() !== null;
    }

    public function delete(User $user, Component $component): bool
    {
        return tenant() !== null;
    }
}
