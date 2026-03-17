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
        $expectedUser = (string) config('ownerrez.webhook.user');
        $expectedPassword = (string) config('ownerrez.webhook.password');

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
