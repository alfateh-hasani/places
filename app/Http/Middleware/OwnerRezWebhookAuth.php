<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerRezWebhookAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUser = config('services.ownerrez.webhook_user');
        $expectedPassword = config('services.ownerrez.webhook_password');

        $providedUser = $request->getUser();
        $providedPassword = $request->getPassword();

        $isValidUser = $expectedUser !== '' && hash_equals($expectedUser, (string) $providedUser);
        $isValidPassword = $expectedPassword !== '' && hash_equals($expectedPassword, (string) $providedPassword);

        if (! $isValidUser || ! $isValidPassword) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized webhook request.');
        }

        return $next($request);
    }
}
