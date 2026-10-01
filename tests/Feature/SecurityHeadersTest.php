<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

/**
 * Web responses carry HSTS (HTTPS only) and a popup-friendly COOP header.
 */
class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/__probe/security-headers', fn (): string => 'ok');
    }

    public function test_https_responses_include_hsts_and_coop(): void
    {
        $this->get('https://localhost/__probe/security-headers')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
    }

    public function test_plain_http_responses_do_not_send_hsts(): void
    {
        $this->get('http://localhost/__probe/security-headers')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
    }

    public function test_web_responses_carry_a_report_only_csp(): void
    {
        $policy = $this->get('https://localhost/__probe/security-headers')
            ->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString('report-uri /csp-report', $policy);
    }

    public function test_csp_reports_are_accepted_without_a_csrf_token_and_logged_without_query_strings(): void
    {
        $logger = Mockery::mock();
        $logger->shouldReceive('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $context['directive'] === 'img-src' && $context['blocked'] === 'https://example.test'
        );
        Log::shouldReceive('channel')->with('csp')->once()->andReturn($logger);

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => ['effective-directive' => 'img-src', 'blocked-uri' => 'https://example.test/a.png?token=secret'],
        ]))->assertNoContent();
    }

    public function test_api_routes_are_not_affected(): void
    {
        Route::middleware('api')->get('/api/__probe/security-headers', fn (): string => 'ok');

        $this->get('https://localhost/api/__probe/security-headers')
            ->assertHeaderMissing('Cross-Origin-Opener-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }
}
