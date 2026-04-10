<?php

namespace App\Services;

use App\Models\Central\Plan;
use App\Models\Central\User;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\Tenant;

class TenantService
{
    public function createTenantForUser(User $user, ?string $domain = null): Tenant
    {
        $tenant = \Stancl\Tenancy\Tenant::create([
            'name' => $user->company_name ?? $user->name . "'s Workspace",
            'user_id' => $user->id,
            'plan_id' => $user->current_plan_id,
        ]);

        if ($domain) {
            $tenant->domains()->create([
                'domain' => $domain,
            ]);
        } else {
            $slug = \Illuminate\Support\Str::slug($user->name . '-' . \Illuminate\Support\Str::random(6));
            $centralDomain = config('tenancy.central_domains')[0] ?? 'pulsewatch.test';
            $tenantDomain = "{$slug}.{$centralDomain}";

            $tenant->domains()->create([
                'domain' => $tenantDomain,
            ]);
        }

        return $tenant;
    }

    public function updateTenantPlan(Tenant $tenant, Plan $plan): void
    {
        $tenant->update([
            'plan_id' => $plan->id,
        ]);
    }

    public function canCreateMonitor(User $user): bool
    {
        $plan = $user->plan;

        if (!$plan) {
            return false;
        }

        $tenant = tenant();

        if (!$tenant) {
            return false;
        }

        $currentCount = \App\Models\Tenant\Monitor::count();

        return $currentCount < $plan->max_monitors;
    }

    public function canAddTeamMember(User $user): bool
    {
        $plan = $user->plan;

        if (!$plan || !$plan->has_team_members) {
            return false;
        }

        $tenant = tenant();

        if (!$tenant) {
            return false;
        }

        $currentMembers = \App\Models\Central\User::where('id', '!=', $user->id)->count();

        return $currentMembers < $plan->max_team_members;
    }

    public function getMonitorLimit(User $user): int
    {
        return $user->plan?->max_monitors ?? 5;
    }

    public function getCheckIntervalLimit(User $user): int
    {
        return $user->plan?->check_interval_seconds ?? 300;
    }
}
