<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Monitor;
use App\Models\Tenant\MonitorLog;
use Illuminate\Support\Carbon;

class UptimeChart extends Component
{
    public int $monitorId;
    public array $uptimeData = [];
    public int $days = 90;

    public function mount(int $monitorId): void
    {
        $this->monitorId = $monitorId;
        $this->loadData();
    }

    public function loadData(): void
    {
        $monitor = Monitor::find($this->monitorId);

        if (!$monitor) {
            return;
        }

        $this->uptimeData = [];

        for ($i = $this->days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStart = $date->startOfDay();
            $dayEnd = $date->endOfDay();

            $total = MonitorLog::where('monitor_id', $this->monitorId)
                ->whereBetween('checked_at', [$dayStart, $dayEnd])
                ->count();

            $up = MonitorLog::where('monitor_id', $this->monitorId)
                ->whereBetween('checked_at', [$dayStart, $dayEnd])
                ->where('status', true)
                ->count();

            $percentage = $total > 0 ? round(($up / $total) * 100, 2) : null;

            $this->uptimeData[] = [
                'date' => $date->format('Y-m-d'),
                'percentage' => $percentage,
                'total' => $total,
                'up' => $up,
            ];
        }
    }

    public function render()
    {
        return view('livewire.tenant.uptime-chart');
    }
}
