<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Receives browser reports for the Content-Security-Policy-Report-Only header
 * (see SecurityHeaders). Each distinct directive + blocked origin is logged once
 * a day to the "csp" channel, so the policy can be tightened before enforcing.
 */
class CspReportController extends Controller
{
    private const MAX_BODY_BYTES = 8192;

    public function __invoke(Request $request): Response
    {
        $body = substr($request->getContent(), 0, self::MAX_BODY_BYTES);
        $report = json_decode($body, true)['csp-report'] ?? null;

        if (! is_array($report)) {
            return response()->noContent();
        }

        $directive = (string) ($report['effective-directive'] ?? $report['violated-directive'] ?? '');
        $blocked = $this->origin((string) ($report['blocked-uri'] ?? ''));
        $source = (string) ($report['source-file'] ?? '');

        if ($directive === '' || $this->isBrowserExtension($blocked) || $this->isBrowserExtension($source)) {
            return response()->noContent();
        }

        if (Cache::add('csp-report:'.md5($directive.'|'.$blocked), true, now()->addDay())) {
            Log::channel('csp')->warning('CSP violation (report-only)', [
                'directive' => $directive,
                'blocked' => $blocked,
                'page' => $this->origin((string) ($report['document-uri'] ?? ''), keepPath: true),
                'source' => $this->origin($source, keepPath: true),
                'line' => $report['line-number'] ?? null,
            ]);
        }

        return response()->noContent();
    }

    /**
     * Reduce a URI to scheme://host (or keyword such as "inline"/"eval"), dropping
     * query strings that may carry personal data.
     */
    private function origin(string $uri, bool $keepPath = false): string
    {
        $parts = parse_url($uri);

        if (! isset($parts['scheme'], $parts['host'])) {
            return substr($uri, 0, 64);
        }

        return $parts['scheme'].'://'.$parts['host'].($keepPath ? ($parts['path'] ?? '') : '');
    }

    private function isBrowserExtension(string $uri): bool
    {
        return (bool) preg_match('#^(chrome|moz|safari|safari-web|ms-browser)-extension:#', $uri);
    }
}
