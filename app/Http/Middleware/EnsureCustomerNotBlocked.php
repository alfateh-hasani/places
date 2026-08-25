<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A customer can be blocked while already holding a valid web session (blocking only
 * gates fresh logins otherwise). This re-checks on every request behind 'auth:customer'
 * and force-logs-out a customer who was blocked mid-session, instead of leaving their
 * existing session usable until it naturally expires.
 */
class EnsureCustomerNotBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && $customer->isBlocked()) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('site.account_blocked'),
                ], 403);
            }

            return redirect()->route('home')->with('error', trans('site.account_blocked'));
        }

        return $next($request);
    }
}
