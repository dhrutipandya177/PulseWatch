<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitor;
use App\Models\Tenant\Monitor;
use Illuminate\Console\Command;

class DispatchMonitorChecks extends Command
{
    protected $signature = 'pulsewatch:dispatch-checks';
    protected $description = 'Dispatch monitor check jobs for all active monitors due for checking';

    public function handle(): int
    {
        $monitors = Monitor::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('last_checked_at')
                    ->orWhereRaw('TIMESTAMPDIFF(SECOND, last_checked_at, NOW()) >= check_interval_seconds');
            })
            ->get();

        $count = 0;

        foreach ($monitors as $monitor) {
            CheckMonitor::dispatch($monitor);
            $count++;
        }

        $this->info("Dispatched {$count} monitor checks.");

        return Command::SUCCESS;
    }
}
