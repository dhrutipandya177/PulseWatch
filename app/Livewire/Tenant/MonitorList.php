<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Monitor;
use App\Models\Central\Plan;
use Illuminate\Support\Facades\Auth;

class MonitorList extends Component
{
    public string $search = '';
    public string $statusFilter = 'all';
    public int $perPage = 20;

    protected $listeners = ['monitor-created' => '$refresh'];

    public function toggleMonitor(int $monitorId): void
    {
        $monitor = Monitor::findOrFail($monitorId);
        $monitor->update(['is_active' => !$monitor->is_active]);
    }

    public function deleteMonitor(int $monitorId): void
    {
        $monitor = Monitor::findOrFail($monitorId);
        $monitor->delete();

        $this->dispatch('monitor-deleted');
    }

    public function render()
    {
        $query = Monitor::with('component');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter === 'up') {
            $query->where('last_status', true);
        } elseif ($this->statusFilter === 'down') {
            $query->where('last_status', false);
        } elseif ($this->statusFilter === 'paused') {
            $query->where('is_active', false);
        }

        $monitors = $query->orderBy('name')->paginate($this->perPage);

        $user = Auth::user();
        $limit = $user->plan?->max_monitors ?? 5;

        return view('livewire.tenant.monitor-list', compact('monitors', 'limit'));
    }
}
