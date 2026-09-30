<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission gate for admin-panel routes that aren't Backpack CRUD screens.
 * Laravel's `can:` middleware checks the default guard, but staff authenticate on
 * the `backpack` guard, so this checks backpack_user() instead.
 *
 * Usage: ->middleware('staff.can:apartment.update')
 */
class EnsureStaffCan
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(backpack_user()?->can($permission), 403, 'Unauthorized Access');

        return $next($request);
    }
}
