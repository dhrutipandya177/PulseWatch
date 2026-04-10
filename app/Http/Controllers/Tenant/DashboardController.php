<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Monitor;
use App\Models\Tenant\Component;
use App\Models\Tenant\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalMonitors = Monitor::count();
        $activeMonitors = Monitor::where('is_active', true)->count();
        $monitorsUp = Monitor::where('last_status', true)->count();
        $monitorsDown = Monitor::where('last_status', false)->count();
        $activeIncidents = Incident::whereIn('status', ['investigating', 'identified', 'monitoring'])->count();
        $totalComponents = Component::count();

        $recentLogs = \App\Models\Tenant\MonitorLog::with('monitor')
            ->orderByDesc('checked_at')
            ->limit(20)
            ->get();

        $recentIncidents = Incident::with('component')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $monitors = Monitor::orderBy('name')->get();

        return view('tenant.dashboard.index', compact(
            'totalMonitors',
            'activeMonitors',
            'monitorsUp',
            'monitorsDown',
            'activeIncidents',
            'totalComponents',
            'recentLogs',
            'recentIncidents',
            'monitors',
        ));
    }
}
