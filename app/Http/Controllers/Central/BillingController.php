<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Plan;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;

class BillingController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function selectPlan()
    {
        $plans = Plan::where('is_active', true)->orderBy('price_cents')->get();
        $currentPlan = Auth::user()->plan;

        return view('central.billing.select-plan', compact('plans', 'currentPlan'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = Auth::user();

        $user->update(['current_plan_id' => $plan->id]);

        // If no Stripe subscription yet, redirect to Stripe checkout
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        if ($user->subscribed('default')) {
            return redirect()->route('tenant.dashboard');
        }

        return $user
            ->newSubscription('default', $plan->stripe_price_id)
            ->trialDays($plan->trial_days)
            ->checkout([
                'success_url' => route('billing.success'),
                'cancel_url' => route('billing.cancel'),
            ]);
    }

    public function portal()
    {
        return Auth::user()
            ->redirectToBillingPortal(route('tenant.dashboard'));
    }

    public function success()
    {
        return view('central.billing.success');
    }

    public function cancel()
    {
        return view('central.billing.cancel');
    }

    public function webhook()
    {
        $payload = request()->all();
        $sigHeader = request()->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                request()->getContent(),
                $sigHeader,
                config('cashier.webhook.secret')
            );
        } catch (\Exception $e) {
            return response('Webhook Error', 400);
        }

        $controller = new \Laravel\Cashier\Http\Controllers\WebhookController();

        return $controller->handleWebhook();
    }
}
