<?php

namespace App\Services;

use App\Models\Tenant\Incident;
use App\Models\Tenant\Subscriber;
use App\Models\Tenant\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public function dispatchForIncident(Incident $incident): void
    {
        if (!$incident->notify_subscribers) {
            return;
        }

        $this->notifySubscribers($incident);
        $this->notifyChannels($incident);
    }

    public function dispatchForMonitorDown($monitor): void
    {
        $activeIncidents = $monitor->component?->activeIncidents() ?? collect();

        foreach ($activeIncidents as $incident) {
            $this->dispatchForIncident($incident);
        }
    }

    protected function notifySubscribers(Incident $incident): void
    {
        if (!$incident->component) {
            return;
        }

        $subscribers = Subscriber::where('is_confirmed', true)
            ->where(function ($query) use ($incident) {
                $query->whereNull('status_page_id')
                    ->orWhereHas('statusPage.components', function ($q) use ($incident) {
                        $q->where('components.id', $incident->component_id);
                    });
            })
            ->get();

        foreach ($subscribers as $subscriber) {
            try {
                Mail::raw($this->buildIncidentEmail($incident), function ($message) use ($subscriber, $incident) {
                    $message->to($subscriber->email)
                        ->subject("Incident: {$incident->title}");
                });
            } catch (\Exception $e) {
                Log::error("Failed to send notification to {$subscriber->email}: {$e->getMessage()}");
            }
        }
    }

    protected function notifyChannels(Incident $incident): void
    {
        $channels = NotificationChannel::where('is_active', true)->get();

        foreach ($channels as $channel) {
            try {
                match ($channel->type) {
                    NotificationChannel::TYPE_SLACK => $this->sendSlackNotification($channel, $incident),
                    NotificationChannel::TYPE_WEBHOOK => $this->sendWebhookNotification($channel, $incident),
                    NotificationChannel::TYPE_EMAIL => $this->sendEmailNotification($channel, $incident),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error("Failed to send notification via {$channel->type}: {$e->getMessage()}");
            }
        }
    }

    protected function sendSlackNotification(NotificationChannel $channel, Incident $incident): void
    {
        $webhookUrl = $channel->getConfigValue('webhook_url');

        if (!$webhookUrl) {
            return;
        }

        $color = match ($incident->status) {
            Incident::STATUS_INVESTIGATING => '#ef4444',
            Incident::STATUS_IDENTIFIED => '#f97316',
            Incident::STATUS_MONITORING => '#eab308',
            Incident::STATUS_RESOLVED => '#22c55e',
            default => '#6b7280',
        };

        Http::post($webhookUrl, [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $incident->title,
                    'text' => $incident->description,
                    'fields' => [
                        ['title' => 'Status', 'value' => $incident->status_label, 'short' => true],
                        ['title' => 'Severity', 'value' => $incident->severity, 'short' => true],
                        ['title' => 'Component', 'value' => $incident->component?->name ?? 'N/A', 'short' => true],
                    ],
                    'footer' => config('app.name'),
                    'ts' => now()->timestamp,
                ],
            ],
        ]);
    }

    protected function sendWebhookNotification(NotificationChannel $channel, Incident $incident): void
    {
        $url = $channel->getConfigValue('url');

        if (!$url) {
            return;
        }

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-PulseWatch-Signature' => hash_hmac('sha256', json_encode($incident->toArray()), $channel->getConfigValue('secret', '')),
        ])->post($url, [
            'event' => 'incident.' . $incident->status,
            'incident' => [
                'id' => $incident->id,
                'title' => $incident->title,
                'status' => $incident->status,
                'severity' => $incident->severity,
                'component' => $incident->component?->name,
                'created_at' => $incident->created_at->toISOString(),
            ],
        ]);
    }

    protected function sendEmailNotification(NotificationChannel $channel, Incident $incident): void
    {
        $email = $channel->getConfigValue('email');

        if (!$email) {
            return;
        }

        Mail::raw($this->buildIncidentEmail($incident), function ($message) use ($email, $incident) {
            $message->to($email)
                ->subject("[{$channel->name}] Incident: {$incident->title}");
        });
    }

    protected function buildIncidentEmail(Incident $incident): string
    {
        $lines = [
            "Incident: {$incident->title}",
            "",
            "Status: {$incident->status_label}",
            "Severity: {$incident->severity}",
            "Component: " . ($incident->component?->name ?? 'N/A'),
            "",
        ];

        if ($incident->description) {
            $lines[] = "Description:";
            $lines[] = $incident->description;
        }

        $latestUpdate = $incident->updates()->first();

        if ($latestUpdate) {
            $lines[] = "";
            $lines[] = "Latest Update ({$latestUpdate->status_label}):";
            $lines[] = $latestUpdate->message;
        }

        $lines[] = "";
        $lines[] = "View status page: " . url('/status');

        return implode("\n", $lines);
    }
}
