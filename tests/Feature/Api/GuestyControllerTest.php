<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.api_secret_key' => 'secret-key',
            'services.guesty.client_id' => 'id',
            'services.guesty.client_secret' => 'secret',
            'services.guesty.base_url' => 'https://open-api.guesty.com',
            'services.guesty.token_url' => 'https://open-api.guesty.com/oauth2/token',
        ]);

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_it_returns_access_token_payload(): void
    {
        Http::fake([
            'https://open-api.guesty.com/oauth2/token' => Http::response([
                'access_token' => 'token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $response = $this->withHeaders($this->secretHeaders())->getJson('/api/guesty/token');

        $response->assertOk()
            ->assertJsonFragment([
                'access_token' => 'token',
                'token_type' => 'Bearer',
            ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://open-api.guesty.com/oauth2/token'
                && $request['clientId'] === 'id'
                && $request['clientSecret'] === 'secret';
        });

        Http::assertSentCount(1);
    }

    public function test_it_fetches_listings_from_guesty(): void
    {
        Http::fake([
            'https://open-api.guesty.com/oauth2/token' => Http::response([
                'access_token' => 'token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'https://open-api.guesty.com/v1/listings*' => Http::response([
                'data' => [
                    ['id' => 'listing-1'],
                ],
            ], 200),
        ]);

        $response = $this->withHeaders($this->secretHeaders())->getJson('/api/guesty/listings?limit=5');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 'listing-1');

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/v1/listings')
                && str_contains($request->url(), 'limit=5');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://open-api.guesty.com/oauth2/token'
                && $request['clientId'] === 'id';
        });

        Http::assertSentCount(2);
    }

    private function secretHeaders(): array
    {
        return ['x-secret-key' => 'secret-key'];
    }
}
