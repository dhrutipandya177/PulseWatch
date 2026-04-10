<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Monitor;
use App\Models\Tenant\Component as TenantComponent;
use App\Jobs\CheckMonitor;

class MonitorForm extends Component
{
    public ?int $monitorId = null;

    public string $name = '';
    public string $url = '';
    public string $method = 'GET';
    public string $type = 'http';
    public int $check_interval_seconds = 300;
    public int $timeout_seconds = 30;
    public int $expected_status_code = 200;
    public ?string $headersJson = '';
    public ?string $expectedContent = '';
    public bool $follow_redirects = true;
    public bool $verify_ssl = true;
    public bool $isEditing = false;

    public function mount(?int $monitorId = null): void
    {
        if ($monitorId) {
            $this->isEditing = true;
            $monitor = Monitor::findOrFail($monitorId);
            $this->monitorId = $monitor->id;
            $this->name = $monitor->name;
            $this->url = $monitor->url;
            $this->method = $monitor->method;
            $this->type = $monitor->type;
            $this->check_interval_seconds = $monitor->check_interval_seconds;
            $this->timeout_seconds = $monitor->timeout_seconds;
            $this->expected_status_code = $monitor->expected_status_code;
            $this->headersJson = $monitor->headers ? json_encode($monitor->headers, JSON_PRETTY_PRINT) : '';
            $this->expectedContent = $monitor->expected_content ? implode("\n", $monitor->expected_content) : '';
            $this->follow_redirects = $monitor->follow_redirects;
            $this->verify_ssl = $monitor->verify_ssl;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'type' => ['required', 'in:http,ping,port,ssl'],
            'check_interval_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'],
            'expected_status_code' => ['nullable', 'integer'],
            'headersJson' => ['nullable', 'string'],
            'expectedContent' => ['nullable', 'string'],
            'follow_redirects' => ['boolean'],
            'verify_ssl' => ['boolean'],
        ]);

        $headers = null;
        if ($this->headersJson) {
            $headers = json_decode($this->headersJson, true);
        }

        $expectedContent = null;
        if ($this->expectedContent) {
            $expectedContent = explode("\n", trim($this->expectedContent));
        }

        $data = [
            'name' => $this->name,
            'url' => $this->url,
            'method' => $this->method,
            'type' => $this->type,
            'check_interval_seconds' => $this->check_interval_seconds,
            'timeout_seconds' => $this->timeout_seconds,
            'expected_status_code' => $this->expected_status_code,
            'headers' => $headers,
            'expected_content' => $expectedContent,
            'follow_redirects' => $this->follow_redirects,
            'verify_ssl' => $this->verify_ssl,
        ];

        if ($this->isEditing && $this->monitorId) {
            $monitor = Monitor::findOrFail($this->monitorId);
            $monitor->update($data);
        } else {
            $monitor = Monitor::create($data);
            CheckMonitor::dispatch($monitor);
        }

        $this->dispatch('monitor-created');
        $this->redirectRoute('tenant.monitors.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.tenant.monitor-form');
    }
}
