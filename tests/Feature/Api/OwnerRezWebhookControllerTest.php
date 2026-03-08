<?php

namespace Tests\Feature\Api;

use App\Jobs\OwnerRez\ProcessWebhookJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OwnerRezWebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ownerrez.webhook.user' => 'webhook-user',
            'ownerrez.webhook.password' => 'webhook-pass',
            'ownerrez.webhook.cache_ttl' => 180,
            'ownerrez.sync.auto_sync_inbound' => true,
        ]);

        Cache::flush();
    }

    public function test_it_returns_401_without_credentials(): void
    {
        $response = $this->postJson('/api/ownerrez', $this->entityCreatePayload());

        $response->assertUnauthorized();
    }

    public function test_it_returns_401_with_wrong_credentials(): void
    {
        $response = $this->withBasicAuth('wrong', 'wrong')
            ->postJson('/api/ownerrez', $this->entityCreatePayload());

        $response->assertUnauthorized();
    }

    public function test_it_returns_200_on_entity_create_webhook(): void
    {
        Queue::fake();

        $response = $this->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/ownerrez', $this->entityCreatePayload());

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);
    }

    public function test_it_dispatches_process_webhook_job_on_entity_create(): void
    {
        Queue::fake();

        $this->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/ownerrez', $this->entityCreatePayload());

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job) {
            return $job->webhookData['action'] === 'entity_create'
                && $job->webhookData['event'] === 'booking'
                && $job->webhookData['data']['id'] === 16879744
                && $job->webhookData['data']['is_block'] === true;
        });
    }

    public function test_it_dispatches_process_webhook_job_on_entity_update(): void
    {
        Queue::fake();

        $this->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/ownerrez', $this->entityUpdatePayload());

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job) {
            return $job->webhookData['action'] === 'entity_update'
                && $job->webhookData['data']['id'] === 16879744
                && $job->webhookData['data']['is_block'] === false
                && $job->webhookData['data']['status'] === 'active';
        });
    }

    public function test_it_stores_payload_in_cache_after_receiving_webhook(): void
    {
        Queue::fake();

        $this->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/ownerrez', $this->entityCreatePayload());

        $cached = Cache::get('ownerrez:webhook:latest');

        $this->assertNotNull($cached);
        $this->assertEquals('entity_create', $cached['action']);
        $this->assertEquals('booking', $cached['event']);
        $this->assertEquals(16879744, $cached['data']['id']);
    }

    public function test_it_does_not_dispatch_job_when_auto_sync_is_disabled(): void
    {
        Queue::fake();
        config(['ownerrez.sync.auto_sync_inbound' => false]);

        $this->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/ownerrez', $this->entityCreatePayload());

        Queue::assertNothingPushed();
    }

    public function test_it_returns_200_on_entity_update_webhook(): void
    {
        Queue::fake();

        $response = $this->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/ownerrez', $this->entityUpdatePayload());

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Payloads from real OwnerRez webhook samples
    // -------------------------------------------------------------------------

    /**
     * entity_create payload — block booking (is_block: true)
     * This is what OwnerRez sends when a new Airbnb hold/block is created.
     */
    private function entityCreatePayload(): array
    {
        return [
            'action' => 'entity_create',
            'entity' => [
                'adults' => 0,
                'arrival' => '2026-03-12',
                'booked_utc' => '2026-03-08T17:54:44Z',
                'check_in' => '16:00',
                'check_out' => '11:00',
                'children' => 0,
                'created_utc' => '2026-03-08T17:54:44Z',
                'currency_code' => 'SAR',
                'departure' => '2026-03-16',
                'id' => 16879744,
                'infants' => 0,
                'is_block' => true,
                'listing_site' => 'Airbnb',
                'platform_reservation_number' => 'HMCFDR58EZ',
                'property' => ['id' => 474841, 'name' => 'S402'],
                'property_id' => 474841,
                'status' => 'pending',
                'title' => 'Hold for HMCFDR58EZ',
                'type' => 'block',
            ],
            'entity_id' => 16879744,
            'entity_type' => 'booking',
            'id' => '17e9b27d-2d93-42ef-835c-1201c28ebe90',
            'user_id' => 347482730,
        ];
    }

    /**
     * entity_update payload — real booking (is_block: false, status: active)
     * This is what OwnerRez sends when the Airbnb hold converts to a confirmed booking.
     */
    private function entityUpdatePayload(): array
    {
        return [
            'action' => 'entity_update',
            'categories' => ['availability', 'charge', 'financial'],
            'entity' => [
                'adults' => 2,
                'arrival' => '2026-03-12',
                'booked_utc' => '2026-03-08T17:54:45Z',
                'check_in' => '15:00',
                'check_out' => '12:00',
                'children' => 0,
                'created_utc' => '2026-03-08T17:54:44Z',
                'currency_code' => 'SAR',
                'departure' => '2026-03-16',
                'guest' => [
                    'first_name' => 'Ibrahim',
                    'id' => 620763955,
                    'last_name' => 'Alshahrani',
                ],
                'guest_id' => 620763955,
                'id' => 16879744,
                'infants' => 0,
                'is_block' => false,
                'listing_site' => 'Airbnb',
                'platform_reservation_number' => 'HMCFDR58EZ',
                'property' => ['id' => 474841, 'name' => 'S402'],
                'property_id' => 474841,
                'status' => 'active',
                'total_amount' => 830.00,
                'total_owed' => 830.00,
                'type' => 'booking',
                'updated_utc' => '2026-03-08T17:56:10Z',
            ],
            'entity_id' => 16879744,
            'entity_type' => 'booking',
            'id' => '47af00f8-44c9-42ba-ae66-0e4b4cc658eb',
            'user_id' => 347482730,
        ];
    }
}
