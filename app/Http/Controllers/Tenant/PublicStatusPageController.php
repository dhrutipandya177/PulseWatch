<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\StatusPage;
use App\Models\Tenant\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PublicStatusPageController extends Controller
{
    public function show(string $slug, Request $request)
    {
        $statusPage = StatusPage::with(['components.monitor.sslCertificate'])
            ->where('slug', $slug)
            ->firstOrFail();

        if (!$statusPage->is_public && !Auth::check()) {
            abort(403, 'This status page is not public.');
        }

        if ($statusPage->password_protected) {
            if (!$request->session()->has("status_page_access_{$statusPage->id}")) {
                return view('status-page.password', compact('statusPage'));
            }
        }

        $components = $statusPage->components;

        // Group components by group_name
        $groupedComponents = $components->groupBy('group_name');

        // Get recent incidents for each component
        foreach ($components as $component) {
            $component->recent_incidents = $component->incidents()
                ->with('updates')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        // Get all recent incidents
        $recentIncidents = \App\Models\Tenant\Incident::with(['component', 'updates'])
            ->whereIn('component_id', $components->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('status-page.show', compact('statusPage', 'groupedComponents', 'recentIncidents', 'components'));
    }

    public function checkPassword(Request $request, string $slug)
    {
        $statusPage = StatusPage::where('slug', $slug)->firstOrFail();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (Hash::check($request->password, $statusPage->password_hash)) {
            $request->session()->put("status_page_access_{$statusPage->id}", true);

            return redirect()->route('status-page.public', $slug);
        }

        return back()->with('error', 'Incorrect password.');
    }

    public function subscribe(Request $request, string $slug)
    {
        $statusPage = StatusPage::where('slug', $slug)
            ->where('allow_subscriber_signup', true)
            ->firstOrFail();

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $subscriber = Subscriber::firstOrCreate(
            [
                'email' => $validated['email'],
                'status_page_id' => $statusPage->id,
            ],
            [
                'token' => \Illuminate\Support\Str::random(64),
                'component_subscriptions' => null,
            ]
        );

        if (!$subscriber->wasRecentlyCreated && !$subscriber->is_confirmed) {
            // Re-send confirmation
            $subscriber->update([
                'token' => \Illuminate\Support\Str::random(64),
            ]);
        }

        // Send confirmation email
        $confirmUrl = url("/subscribe/confirm/{$subscriber->token}");

        \Illuminate\Support\Facades\Mail::raw(
            "Click the link below to confirm your subscription:\n\n{$confirmUrl}",
            function ($message) use ($subscriber, $statusPage) {
                $message->to($subscriber->email)
                    ->subject("Confirm your subscription to {$statusPage->title}");
            }
        );

        return back()->with('success', 'Please check your email to confirm your subscription.');
    }
}
