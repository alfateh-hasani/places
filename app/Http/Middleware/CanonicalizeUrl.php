<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Every public page must answer 200 on exactly one URL. Variants that otherwise render the same
 * content (an explicit default-locale prefix such as /ar/faq, an upper-case prefix such as /EN,
 * or a trailing slash) are sent to the canonical form with a permanent redirect.
 */
class CanonicalizeUrl
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || $request->is('api/*', 'admin', 'admin/*')) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        $canonical = $this->canonicalPath($path);

        if ($canonical === $path) {
            return $next($request);
        }

        $query = $request->getQueryString();

        return redirect()->to($request->getSchemeAndHttpHost().$canonical.($query ? '?'.$query : ''), Response::HTTP_MOVED_PERMANENTLY);
    }

    private function canonicalPath(string $path): string
    {
        $segments = array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return '/';
        }

        $supported = array_keys(LaravelLocalization::getSupportedLocales());
        $first = strtolower($segments[0]);

        if (in_array($first, $supported, true)) {
            $hidden = LaravelLocalization::hideDefaultLocaleInURL() && $first === LaravelLocalization::getDefaultLocale();

            if ($hidden) {
                array_shift($segments);
            } else {
                $segments[0] = $first;
            }
        }

        return '/'.implode('/', $segments);
    }
}
