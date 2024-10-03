<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class ApiLocaleKeyMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        $language = $request->header('x-language');
        if (in_array($language, array_keys(config('app.locales')))) {
            app()->setLocale($language);
        }
        return $next($request);
    }
}
