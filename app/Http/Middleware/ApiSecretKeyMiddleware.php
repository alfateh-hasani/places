<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSecretKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $secretKey = config('app.api_secret_key');
        if (!$request->header('x-secret-key') || $request->header('x-secret-key') !== $secretKey ) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
