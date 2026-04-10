<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Component as TenantComponent;
use App\Models\Tenant\Monitor;

class ComponentForm extends Component
{
    public ?int $componentId = null;
    public bool $isEditing = false;

    public string $name = '';
    public ?string $slug = '';
    public ?string $description = '';
    public string $status = TenantComponent::STATUS_OPERATIONAL;
    public ?string $group_name = '';
    public int $sort_order = 0;
    public bool $show_uptime_percentage = true;
    public ?int $monitor_id = null;

    public function mount(?int $componentId = null): void
    {
        if ($componentId) {
            $this->isEditing = true;
            $component = TenantComponent::findOrFail($componentId);
            $this->componentId = $component->id;
            $this->name = $component->name;
            $this->slug = $component->slug;
            $this->description = $component->description;
            $this->status = $component->status;
            $this->group_name = $component->group_name;
            $this->sort_order = $component->sort_order;
            $this->show_uptime_percentage = $component->show_uptime_percentage;
            $this->monitor_id = $component->monitor_id;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:components,slug,' . ($this->componentId ?? 'NULL')],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:operational,degraded,outage,maintenance'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'show_uptime_percentage' => ['boolean'],
            'monitor_id' => ['nullable', 'exists:monitors,id'],
        ]);

        if ($this->isEditing && $this->componentId) {
            $component = TenantComponent::findOrFail($this->componentId);
            $component->update($validated);
        } else {
            TenantComponent::create($validated);
        }

        $this->redirectRoute('tenant.components.index', navigate: true);
    }

    public function render()
    {
        $monitors = Monitor::orderBy('name')->get();

        return view('livewire.tenant.component-form', compact('monitors'));
    }
}
