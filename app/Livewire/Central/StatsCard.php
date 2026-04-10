<?php

namespace App\Livewire\Central;

use Livewire\Component;

class StatsCard extends Component
{
    public string $title;
    public string|int|float $value;
    public string $icon;
    public string $color;
    public ?string $trend = null;

    public function render()
    {
        return view('livewire.central.stats-card');
    }
}
