<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Incident;

class IncidentTimeline extends Component
{
    public int $limit = 10;

    protected $listeners = ['incident-created' => '$refresh'];

    public function render()
    {
        $incidents = Incident::with(['component', 'updates'])
            ->orderByDesc('created_at')
            ->limit($this->limit)
            ->get();

        return view('livewire.tenant.incident-timeline', compact('incidents'));
    }
}
