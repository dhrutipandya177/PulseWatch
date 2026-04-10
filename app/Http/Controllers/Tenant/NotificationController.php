<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\NotificationChannel;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $channels = NotificationChannel::orderBy('name')->get();

        return view('tenant.settings.notifications', compact('channels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:email,slack,webhook,sms'],
            'is_active' => ['boolean'],
            'config' => ['required', 'array'],
        ]);

        NotificationChannel::create($validated);

        return redirect()->route('tenant.settings.notifications')
            ->with('success', 'Notification channel added.');
    }

    public function update(Request $request, NotificationChannel $channel)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:email,slack,webhook,sms'],
            'is_active' => ['boolean'],
            'config' => ['required', 'array'],
        ]);

        $channel->update($validated);

        return redirect()->route('tenant.settings.notifications')
            ->with('success', 'Notification channel updated.');
    }

    public function destroy(NotificationChannel $channel)
    {
        $channel->delete();

        return back()->with('success', 'Notification channel removed.');
    }

    public function test(NotificationChannel $channel)
    {
        $dispatcher = app(\App\Services\NotificationDispatcher::class);

        $testIncident = new \App\Models\Tenant\Incident([
            'title' => 'Test Notification',
            'description' => 'This is a test notification from ' . config('app.name'),
            'status' => 'investigating',
            'severity' => 'minor',
        ]);

        try {
            match ($channel->type) {
                NotificationChannel::TYPE_SLACK => $dispatcher->sendSlackNotification($channel, $testIncident),
                NotificationChannel::TYPE_WEBHOOK => $dispatcher->sendWebhookNotification($channel, $testIncident),
                NotificationChannel::TYPE_EMAIL => $dispatcher->sendEmailNotification($channel, $testIncident),
                default => null,
            };

            return back()->with('success', 'Test notification sent.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test notification: ' . $e->getMessage());
        }
    }
}
