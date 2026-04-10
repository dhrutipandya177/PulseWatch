<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index()
    {
        $subscribers = Subscriber::with('statusPage')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('tenant.team.subscribers', compact('subscribers'));
    }

    public function confirm(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $subscriber->confirm();

        return redirect()->route('status-page.public', $subscriber->statusPage?->slug ?? '')
            ->with('success', 'You have been subscribed to notifications.');
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
