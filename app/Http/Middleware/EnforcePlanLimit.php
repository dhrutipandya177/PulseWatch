<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforcePlanLimit
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $plan = $user->plan;

        if (!$plan) {
            return redirect()->route('billing.index')
                ->with('error', 'Please select a plan to continue.');
        }

        $featureMap = [
            'custom_domain' => 'has_custom_domain',
            'team_members' => 'has_team_members',
            'status_page' => 'has_status_page',
            'ssl_monitoring' => 'has_ssl_monitoring',
            'email_notifications' => 'has_email_notifications',
            'sms_notifications' => 'has_sms_notifications',
            'slack_notifications' => 'has_slack_notifications',
            'webhook_notifications' => 'has_webhook_notifications',
        ];

        $planField = $featureMap[$feature] ?? null;

        if ($planField && !$plan->{$planField}) {
            return redirect()->route('billing.index')
                ->with('error', "Your plan does not support {$feature}. Please upgrade.");
        }

        return $next($request);
    }
}
