<?php

namespace App\Console\Commands;

use App\Models\Tenant\MonitorLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneMonitorLogs extends Command
{
    protected $signature = 'pulsewatch:prune-logs {--days=30 : Number of days to retain logs}';
    protected $description = 'Prune old monitor logs to manage database size';

    public function handle(): int
    {
        $days = $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $deleted = MonitorLog::where('checked_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} log entries older than {$days} days.");

        return Command::SUCCESS;
    }
}
