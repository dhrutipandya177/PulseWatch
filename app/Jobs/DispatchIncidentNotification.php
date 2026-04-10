<?php

namespace App\Jobs;

use App\Models\Tenant\Incident;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchIncidentNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Incident $incident
    ) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $dispatcher->dispatchForIncident($this->incident);
    }
}
