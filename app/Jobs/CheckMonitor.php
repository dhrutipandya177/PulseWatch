<?php

namespace App\Jobs;

use App\Models\Tenant\Monitor;
use App\Services\MonitorChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMonitor implements ShouldQueue
{
    use Queueable;

    public $deleteWhenMissingModels = true;

    public function __construct(
        public Monitor $monitor
    ) {}

    public function handle(MonitorChecker $checker): void
    {
        if (!$this->monitor->is_active) {
            return;
        }

        $checker->check($this->monitor);
    }
}
