<?php

namespace Tests\Feature\OwnerRez;

use App\Exceptions\OwnerRez\OwnerRezDisabledException;
use App\Jobs\OwnerRez\ProcessWebhookJob;
use App\Services\OwnerRez\OwnerRezApiService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the global OWNERREZ_ENABLED kill switch. All assertions exercise the
 * disabled path, which short-circuits before any DB or network access, so these
 * tests need no database.
 */
class OwnerRezMasterSwitchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['ownerrez.enabled' => false]);
        Http::fake();
    }

    public function test_api_service_throws_disabled_exception_and_sends_no_request(): void
    {
        $thrown = false;

        try {
            app(OwnerRezApiService::class)->getBooking(123);
        } catch (OwnerRezDisabledException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'Expected OwnerRezDisabledException when the integration is disabled.');
        Http::assertNothingSent();
    }

    public function test_curl_properties_endpoint_throws_disabled_exception(): void
    {
        $this->expectException(OwnerRezDisabledException::class);

        app(OwnerRezApiService::class)->getProperties();
    }

    public function test_check_availability_returns_true_and_sends_no_request(): void
    {
        $available = app(OwnerRezSyncService::class)->checkAvailability(474841, '2026-03-12', '2026-03-16');

        $this->assertTrue($available);
        Http::assertNothingSent();
    }

    public function test_calendar_and_active_bookings_are_empty_and_send_no_request(): void
    {
        $sync = app(OwnerRezSyncService::class);

        $this->assertTrue($sync->getCalendarBookings(474841)->isEmpty());
        $this->assertTrue($sync->getActiveBookings(474841, '2026-03-12', '2026-03-16')->isEmpty());
        $this->assertFalse($sync->refreshCalendarCache(474841));

        Http::assertNothingSent();
    }

    public function test_inbound_webhook_is_acked_without_processing(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/ownerrez', [
            'action' => 'entity_create',
            'entity' => ['id' => 16879744, 'property_id' => 474841],
            'entity_id' => 16879744,
            'entity_type' => 'booking',
            'id' => (string) Str::uuid(),
        ]);

        $response->assertOk();
        Queue::assertNotPushed(ProcessWebhookJob::class);
    }

    public function test_warm_cache_command_no_ops_when_disabled(): void
    {
        $this->artisan('ownerrez:warm-cache')
            ->expectsOutputToContain('disabled')
            ->assertSuccessful();

        Http::assertNothingSent();
    }
}
