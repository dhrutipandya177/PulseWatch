<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\User;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TeamController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function index()
    {
        $members = User::orderBy('name')->get();
        $roles = Role::all();

        return view('tenant.team.index', compact('members', 'roles'));
    }

    public function invite(Request $request)
    {
        if (!$this->tenantService->canAddTeamMember(Auth::user())) {
            return back()->with('error', 'Team member limit reached. Upgrade your plan.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
            'role' => 'team_member',
            'current_plan_id' => Auth::user()->current_plan_id,
        ]);

        if ($validated['role']) {
            $user->assignRole($validated['role']);
        }

        return back()->with('success', 'Team member invited successfully.');
    }

    public function updateRole(User $member, Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $member->syncRoles([$validated['role']]);

        return back()->with('success', 'Role updated.');
    }

    public function remove(User $member)
    {
        if ($member->id === Auth::id()) {
            return back()->with('error', 'You cannot remove yourself.');
        }

        $member->delete();

        return back()->with('success', 'Team member removed.');
    }
}
