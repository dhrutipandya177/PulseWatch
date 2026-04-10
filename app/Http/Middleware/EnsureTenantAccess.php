<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!tenant()) {
            abort(404, 'Tenant not found.');
        }

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
