<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds browser hardening headers to web responses. COOP allows popups so
 * payment / OAuth windows opened from the site keep working.
 *
 * The Content-Security-Policy is sent in Report-Only mode: browsers report
 * violations to /csp-report (logged to storage/logs/csp-*.log) without blocking
 * anything, so the policy can be completed before it is enforced.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Content-Security-Policy-Report-Only', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $mediaHost = parse_url(Storage::disk(config('media-library.disk_name'))->url('media'), PHP_URL_HOST);
        $media = $mediaHost ? 'https://'.$mediaHost : '';

        $scriptHosts = 'https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://npmcdn.com https://oss.maxcdn.com '
            .'https://code.jquery.com https://www.google.com https://www.gstatic.com https://maps.googleapis.com '
            .'https://static.cloudflareinsights.com https://www.googletagmanager.com https://*.google-analytics.com https://*.respond.io';

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' {$scriptHosts}",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://npmcdn.com https://fonts.googleapis.com https://*.respond.io",
            "img-src 'self' data: blob: {$media} https://maps.gstatic.com https://maps.googleapis.com https://www.google.com https://www.gstatic.com https://www.googletagmanager.com https://*.google-analytics.com https://*.respond.io",
            "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
            "connect-src 'self' https://www.google.com https://maps.googleapis.com https://cloudflareinsights.com https://www.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com https://*.respond.io wss://*.respond.io",
            'frame-src https://www.google.com https://www.youtube.com https://www.googletagmanager.com https://*.respond.io',
            "form-action 'self' https://www.ksamerchant.geidea.net",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            'report-uri '.route('csp-report', absolute: false),
        ];

        return implode('; ', array_map('trim', $directives));
    }
}
