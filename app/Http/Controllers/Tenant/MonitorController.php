<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Monitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonitorController extends Controller
{
    public function index()
    {
        $monitors = Monitor::with('component')
            ->orderBy('name')
            ->get();

        return view('tenant.monitors.index', compact('monitors'));
    }

    public function create()
    {
        $user = Auth::user();
        $limit = $user->plan?->max_monitors ?? 5;
        $currentCount = Monitor::count();

        return view('tenant.monitors.create', compact('limit', 'currentCount'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $limit = $user->plan?->max_monitors ?? 5;

        if (Monitor::count() >= $limit) {
            return back()->with('error', 'Monitor limit reached. Upgrade your plan.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'type' => ['required', 'in:http,ping,port,ssl'],
            'check_interval_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'],
            'expected_status_code' => ['nullable', 'integer'],
            'headers' => ['nullable', 'array'],
            'expected_content' => ['nullable', 'array'],
            'follow_redirects' => ['boolean'],
            'verify_ssl' => ['boolean'],
        ]);

        $validated['is_active'] = true;

        if ($request->has('headers') && is_array($request->headers)) {
            $validated['headers'] = collect($request->headers)
                ->filter(fn($v, $k) => !empty($k))
                ->toArray();
        }

        Monitor::create($validated);

        return redirect()->route('tenant.monitors.index')
            ->with('success', 'Monitor created successfully.');
    }

    public function edit(Monitor $monitor)
    {
        return view('tenant.monitors.edit', compact('monitor'));
    }

    public function update(Request $request, Monitor $monitor)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'type' => ['required', 'in:http,ping,port,ssl'],
            'check_interval_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'],
            'expected_status_code' => ['nullable', 'integer'],
            'headers' => ['nullable', 'array'],
            'expected_content' => ['nullable', 'array'],
            'follow_redirects' => ['boolean'],
            'verify_ssl' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if ($request->has('headers') && is_array($request->headers)) {
            $validated['headers'] = collect($request->headers)
                ->filter(fn($v, $k) => !empty($k))
                ->toArray();
        }

        $monitor->update($validated);

        return redirect()->route('tenant.monitors.index')
            ->with('success', 'Monitor updated successfully.');
    }

    public function destroy(Monitor $monitor)
    {
        $monitor->delete();

        return redirect()->route('tenant.monitors.index')
            ->with('success', 'Monitor deleted successfully.');
    }

    public function show(Monitor $monitor)
    {
        $logs = $monitor->logs()->orderByDesc('checked_at')->paginate(50);
        $uptime7d = $monitor->uptimePercentage(7);
        $uptime30d = $monitor->uptimePercentage(30);
        $uptime90d = $monitor->uptimePercentage(90);
        $avgResponseTime = $monitor->averageResponseTime(7);

        return view('tenant.monitors.show', compact(
            'monitor',
            'logs',
            'uptime7d',
            'uptime30d',
            'uptime90d',
            'avgResponseTime',
        ));
    }

    public function toggle(Monitor $monitor)
    {
        $monitor->update(['is_active' => !$monitor->is_active]);

        return back()->with('success', 'Monitor ' . ($monitor->is_active ? 'activated' : 'paused') . '.');
    }
}
