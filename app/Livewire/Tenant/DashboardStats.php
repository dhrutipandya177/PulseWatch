<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Monitor;

class DashboardStats extends Component
{
    public int $totalMonitors = 0;
    public int $monitorsUp = 0;
    public int $monitorsDown = 0;
    public int $activeIncidents = 0;

    protected $listeners = ['refresh-stats' => 'loadStats'];

    public function loadStats(): void
    {
        $this->totalMonitors = Monitor::count();
        $this->monitorsUp = Monitor::where('last_status', true)->count();
        $this->monitorsDown = Monitor::where('last_status', false)->count();
        $this->activeIncidents = \App\Models\Tenant\Incident::whereIn('status', ['investigating', 'identified', 'monitoring'])->count();
    }

    public function mount(): void
    {
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.tenant.dashboard-stats');
    }
}
