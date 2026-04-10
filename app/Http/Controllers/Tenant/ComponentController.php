<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Component;
use App\Models\Tenant\Monitor;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    public function index()
    {
        $components = Component::with('monitor')
            ->orderBy('sort_order')
            ->get();

        return view('tenant.components.index', compact('components'));
    }

    public function create()
    {
        $monitors = Monitor::orderBy('name')->get();
        $groups = Component::whereNotNull('group_name')
            ->distinct()
            ->pluck('group_name');

        return view('tenant.components.create', compact('monitors', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:components,slug'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:operational,degraded,outage,maintenance'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'show_uptime_percentage' => ['boolean'],
            'monitor_id' => ['nullable', 'exists:monitors,id'],
        ]);

        Component::create($validated);

        return redirect()->route('tenant.components.index')
            ->with('success', 'Component created successfully.');
    }

    public function edit(Component $component)
    {
        $monitors = Monitor::orderBy('name')->get();
        $groups = Component::whereNotNull('group_name')
            ->where('id', '!=', $component->id)
            ->distinct()
            ->pluck('group_name');

        return view('tenant.components.edit', compact('component', 'monitors', 'groups'));
    }

    public function update(Request $request, Component $component)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:components,slug,' . $component->id],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:operational,degraded,outage,maintenance'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'show_uptime_percentage' => ['boolean'],
            'monitor_id' => ['nullable', 'exists:monitors,id'],
        ]);

        $component->update($validated);

        return redirect()->route('tenant.components.index')
            ->with('success', 'Component updated successfully.');
    }

    public function destroy(Component $component)
    {
        $component->delete();

        return redirect()->route('tenant.components.index')
            ->with('success', 'Component deleted successfully.');
    }
}
