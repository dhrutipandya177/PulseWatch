<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\NotificationChannel;

class NotificationForm extends Component
{
    public ?int $channelId = null;
    public bool $isEditing = false;

    public string $name = '';
    public string $type = 'email';
    public bool $is_active = true;

    // Email config
    public ?string $email_address = '';

    // Slack config
    public ?string $slack_webhook_url = '';

    // Webhook config
    public ?string $webhook_url = '';
    public ?string $webhook_secret = '';

    // SMS config
    public ?string $phone_number = '';

    public function mount(?int $channelId = null): void
    {
        if ($channelId) {
            $this->isEditing = true;
            $channel = NotificationChannel::findOrFail($channelId);
            $this->channelId = $channel->id;
            $this->name = $channel->name;
            $this->type = $channel->type;
            $this->is_active = $channel->is_active;

            $config = $channel->config;
            $this->email_address = $config['email'] ?? '';
            $this->slack_webhook_url = $config['webhook_url'] ?? '';
            $this->webhook_url = $config['url'] ?? '';
            $this->webhook_secret = $config['secret'] ?? '';
            $this->phone_number = $config['phone'] ?? '';
        }
    }

    public function save(): void
    {
        $config = [];

        match ($this->type) {
            'email' => $config = ['email' => $this->email_address],
            'slack' => $config = ['webhook_url' => $this->slack_webhook_url],
            'webhook' => $config = ['url' => $this->webhook_url, 'secret' => $this->webhook_secret],
            'sms' => $config = ['phone' => $this->phone_number],
            default => $config = [],
        };

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:email,slack,webhook,sms'],
            'is_active' => ['boolean'],
        ]);

        $data = array_merge($validated, ['config' => $config]);

        if ($this->isEditing && $this->channelId) {
            $channel = NotificationChannel::findOrFail($this->channelId);
            $channel->update($data);
        } else {
            NotificationChannel::create($data);
        }

        $this->redirectRoute('tenant.settings.notifications', navigate: true);
    }

    public function render()
    {
        return view('livewire.tenant.notification-form');
    }
}
