<?php

namespace Tests\Feature;

use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Rules\MaxStay;
use App\Services\OwnerRez\OwnerRezApiService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * OwnerRez webhooks are authenticated only by a shared secret, so their body must never be
 * trusted: each action is re-read from the OwnerRez API. Also covers the max-stay rule that
 * keeps per-night pricing from being abused to exhaust PHP workers.
 */
class SecurityPhase3Test extends TestCase
{
    private const ENTITY_ID = 987654321;

    /**
     * @param  array<string, mixed>|OwnerRezApiException  $apiResult
     */
    private function syncServiceReturning(array|OwnerRezApiException $apiResult): OwnerRezSyncService&MockInterface
    {
        $api = Mockery::mock(OwnerRezApiService::class);
        $expectation = $api->shouldReceive('getBooking')->with(self::ENTITY_ID);
        $apiResult instanceof OwnerRezApiException ? $expectation->andThrow($apiResult) : $expectation->andReturn($apiResult);

        return Mockery::mock(OwnerRezSyncService::class, [$api])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    private function notFound(): OwnerRezApiException
    {
        return new OwnerRezApiException('Not found', '/v2/bookings/'.self::ENTITY_ID, [], 404);
    }

    public function test_forged_delete_is_ignored_while_booking_is_live_in_ownerrez(): void
    {
        $service = $this->syncServiceReturning(['id' => self::ENTITY_ID, 'status' => 'active', 'property_id' => 1]);
        $service->shouldNotReceive('handleBookingDeleted');

        $service->syncBookingFromWebhook(['action' => 'entity_delete', 'entity_id' => self::ENTITY_ID, 'data' => []]);
    }

    public function test_delete_proceeds_when_ownerrez_no_longer_has_the_booking(): void
    {
        $service = $this->syncServiceReturning($this->notFound());
        $service->shouldReceive('handleBookingDeleted')->once()->with(['id' => self::ENTITY_ID]);

        $service->syncBookingFromWebhook(['action' => 'entity_delete', 'entity_id' => self::ENTITY_ID, 'data' => []]);
    }

    public function test_create_uses_ownerrez_api_data_not_the_webhook_body(): void
    {
        $apiBooking = ['id' => self::ENTITY_ID, 'status' => 'canceled', 'property_id' => 1, 'guest' => ['first_name' => 'Real']];
        $service = $this->syncServiceReturning($apiBooking);
        $service->shouldReceive('handleBookingCreated')->once()->with($apiBooking);

        $service->syncBookingFromWebhook([
            'action' => 'entity_create',
            'entity_id' => self::ENTITY_ID,
            'data' => ['status' => 'active', 'total_amount' => 0, 'guest' => ['first_name' => 'Forged', 'phones' => [['number' => '+966500000000']]]],
        ]);
    }

    public function test_create_for_a_booking_unknown_to_ownerrez_is_ignored(): void
    {
        $service = $this->syncServiceReturning($this->notFound());
        $service->shouldNotReceive('handleBookingCreated');

        $service->syncBookingFromWebhook(['action' => 'entity_create', 'entity_id' => self::ENTITY_ID, 'data' => ['status' => 'active']]);
    }

    public function test_api_outage_fails_instead_of_falling_back_to_webhook_body(): void
    {
        $service = $this->syncServiceReturning(new OwnerRezApiException('Server error', '/v2/bookings', [], 500));
        $service->shouldNotReceive('handleBookingCreated');

        $this->expectException(OwnerRezApiException::class);
        $service->syncBookingFromWebhook(['action' => 'entity_create', 'entity_id' => self::ENTITY_ID, 'data' => ['status' => 'active']]);
    }

    public function test_max_stay_rejects_ranges_longer_than_the_configured_limit(): void
    {
        config()->set('booking.max_nights', 365);

        $tooLong = Validator::make(['check_in' => '2000-01-01', 'check_out' => '9999-12-31'], ['check_out' => [new MaxStay]]);
        $allowed = Validator::make(['check_in' => '2026-11-01', 'check_out' => '2027-02-01'], ['check_out' => [new MaxStay]]);
        $webFields = Validator::make(['checkin' => '2026-11-01', 'checkout' => '2028-11-01'], ['checkout' => [new MaxStay('checkin')]]);

        $this->assertTrue($tooLong->fails());
        $this->assertTrue($allowed->passes());
        $this->assertTrue($webFields->fails());
    }
}
