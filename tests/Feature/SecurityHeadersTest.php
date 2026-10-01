<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
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
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
    }

    public function test_plain_http_responses_do_not_send_hsts(): void
    {
        $this->get('http://localhost/__probe/security-headers')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
    }

    public function test_api_routes_are_not_affected(): void
    {
        Route::middleware('api')->get('/api/__probe/security-headers', fn (): string => 'ok');

        $this->get('https://localhost/api/__probe/security-headers')
            ->assertHeaderMissing('Cross-Origin-Opener-Policy');
    }
}
