<?php

namespace App\Livewire\Central;

use Livewire\Component;
use App\Models\Central\Plan;
use Illuminate\Support\Facades\Auth;

class PlanSelector extends Component
{
    public ?int $selectedPlanId = null;
    public bool $isAnnual = false;

    public function mount()
    {
        $this->selectedPlanId = Auth::user()->current_plan_id;
    }

    public function selectPlan(int $planId): void
    {
        $this->selectedPlanId = $planId;

        $this->dispatch('plan-selected', planId: $planId);
    }

    public function render()
    {
        $plans = Plan::where('is_active', true)->orderBy('price_cents')->get();

        return view('livewire.central.plan-selector', compact('plans'));
    }
}
