<?php

namespace Tests\Feature\DateChange;

use App\Enums\DateChangeStatus;
use App\Mail\DateChangeRequested;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Customer;
use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Models\DateChangeRequest;
use App\Models\OwnerRezPropertyMapping;
use App\Models\User;
use App\Services\DateChangeService;
use App\Services\OwnerRez\OwnerRezApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DateChangeServiceTest extends TestCase
{
    private DateChangeService $service;

    private array $createdApartmentIds = [];

    private array $createdCustomerIds = [];

    private array $createdBookingIds = [];

    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Mail::fake();

        $this->service = app(DateChangeService::class);
    }

    protected function tearDown(): void
    {
        if ($this->createdUserIds !== []) {
            DB::table('model_has_roles')->whereIn('model_id', $this->createdUserIds)->delete();
            User::whereIn('id', $this->createdUserIds)->forceDelete();
        }

        DateChangeRequest::whereIn('booking_id', $this->createdBookingIds)->delete();
        Booking::whereIn('id', $this->createdBookingIds)->delete();
        OwnerRezPropertyMapping::whereIn('apartment_id', $this->createdApartmentIds)->delete();
        Customer::whereIn('id', $this->createdCustomerIds)->forceDelete();
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();

        parent::tearDown();
    }

    public function test_quote_computes_negative_delta_for_a_shorter_stay(): void
    {
        $booking = $this->createBooking(); // 4 nights @ 200 = 800

        $quote = $this->service->quote(
            $booking,
            $booking->check_in->toDateString(),
            $booking->check_in->copy()->addDays(2)->toDateString(), // 2 nights
        );

        $this->assertSame('refund', $quote['direction']);
        $this->assertEqualsWithDelta(-400.0, $quote['price_delta'], 0.01);
        $this->assertEqualsWithDelta(400.0, $quote['new_price'], 0.01);
    }

    public function test_cheaper_request_creates_pending_review_and_keeps_original_dates(): void
    {
        $booking = $this->createBooking();
        $originalIn = $booking->check_in->toDateString();
        $originalOut = $booking->check_out->toDateString();

        $result = $this->service->request(
            $booking,
            $originalIn,
            $booking->check_in->copy()->addDays(2)->toDateString(),
        );

        $this->assertSame('pending_review', $result['action']);
        $this->assertSame(DateChangeStatus::PendingReview->value, $result['request']->status);

        // Booking dates must NOT change until staff approves.
        $booking->refresh();
        $this->assertSame($originalIn, $booking->check_in->toDateString());
        $this->assertSame($originalOut, $booking->check_out->toDateString());
    }

    public function test_even_price_request_is_applied_directly(): void
    {
        $booking = $this->createBooking();

        // Shift the same 4-night window forward by a week → same flat price → even.
        $newIn = $booking->check_in->copy()->addDays(7);
        $newOut = $booking->check_out->copy()->addDays(7);

        $result = $this->service->request($booking, $newIn->toDateString(), $newOut->toDateString());

        $this->assertSame('applied', $result['action']);
        $this->assertSame(DateChangeStatus::Applied->value, $result['request']->status);

        $booking->refresh();
        $this->assertSame($newIn->toDateString(), $booking->check_in->toDateString());
        $this->assertSame($newOut->toDateString(), $booking->check_out->toDateString());
    }

    public function test_only_one_open_request_is_allowed_per_booking(): void
    {
        $booking = $this->createBooking();

        // First cheaper request → PendingReview (stays open).
        $this->service->request($booking, $booking->check_in->toDateString(), $booking->check_in->copy()->addDays(2)->toDateString());

        $this->expectException(ValidationException::class);

        $this->service->request($booking, $booking->check_in->toDateString(), $booking->check_in->copy()->addDays(3)->toDateString());
    }

    public function test_applying_dates_updates_the_booking_and_recomputes_price(): void
    {
        $booking = $this->createBooking();

        $request = DateChangeRequest::create([
            'booking_id' => $booking->id,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'new_check_in' => $booking->check_in->copy()->addDays(1),
            'new_check_out' => $booking->check_in->copy()->addDays(3), // 2 nights
            'original_price' => 800,
            'new_price' => 400,
            'price_delta' => -400,
            'status' => DateChangeStatus::PendingReview->value,
        ]);

        $updated = $this->service->applyDates($request);

        $this->assertSame(2, (int) $updated->number_of_nights);
        $this->assertEqualsWithDelta(400.0, (float) $updated->final_price, 0.01);
        $this->assertNotNull($request->fresh()->applied_at);
    }

    public function test_cheaper_request_emails_only_configured_reviewers(): void
    {
        $reviewers = $this->createReviewerUsers([
            'dc.reviewer.one@example.com',
            'dc.reviewer.two@example.com',
        ]);
        config(['mail.date_change_reviewers.only_user_ids' => $reviewers->pluck('id')->all()]);

        $booking = $this->createBooking();

        $this->service->request(
            $booking,
            $booking->check_in->toDateString(),
            $booking->check_in->copy()->addDays(2)->toDateString(),
        );

        Mail::assertSent(DateChangeRequested::class, 2);
        foreach ($reviewers as $reviewer) {
            Mail::assertSent(DateChangeRequested::class, fn (DateChangeRequested $mail) => $mail->hasTo($reviewer->email));
        }
    }

    public function test_review_email_restriction_limits_recipients_to_allowed_user_ids(): void
    {
        $reviewers = $this->createReviewerUsers([
            'dc.reviewer.allowed@example.com',
            'dc.reviewer.blocked@example.com',
        ]);
        config(['mail.date_change_reviewers.only_user_ids' => [$reviewers->first()->id]]);

        $booking = $this->createBooking();

        $this->service->request(
            $booking,
            $booking->check_in->toDateString(),
            $booking->check_in->copy()->addDays(2)->toDateString(),
        );

        Mail::assertSent(DateChangeRequested::class, 1);
        Mail::assertSent(DateChangeRequested::class, fn (DateChangeRequested $mail) => $mail->hasTo($reviewers->first()->email));
    }

    private function createReviewerUsers(array $emails): Collection
    {
        $role = \Spatie\Permission\Models\Role::findByName('البرمجة', 'backpack');

        return collect($emails)->map(function (string $email) use ($role): User {
            $user = User::forceCreate([
                'name' => 'DC Reviewer '.substr(md5($email), 0, 6),
                'email' => $email,
                'password' => bcrypt(substr(md5(uniqid('', true)), 0, 16)),
                'phone' => '+9660'.mt_rand(100000000, 999999999),
            ]);
            $this->createdUserIds[] = $user->id;
            $user->assignRole($role);

            return $user;
        });
    }

    public function test_customer_can_cancel_an_open_request_and_free_the_window(): void
    {
        $booking = $this->createBooking();

        $result = $this->service->request(
            $booking,
            $booking->check_in->toDateString(),
            $booking->check_in->copy()->addDays(2)->toDateString(),
        );
        $request = $result['request'];

        $this->service->cancelByCustomer($request);

        $this->assertSame(DateChangeStatus::Rejected->value, $request->fresh()->status);

        // Window is free again → a new request is allowed.
        $again = $this->service->request(
            $booking,
            $booking->check_in->toDateString(),
            $booking->check_in->copy()->addDays(3)->toDateString(),
        );
        $this->assertSame(DateChangeStatus::PendingReview->value, $again['request']->status);
    }

    public function test_expire_stale_releases_awaiting_payment_requests(): void
    {
        $booking = $this->createBooking();

        $request = DateChangeRequest::create([
            'booking_id' => $booking->id,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'new_check_in' => $booking->check_in->copy()->addDays(10),
            'new_check_out' => $booking->check_in->copy()->addDays(16),
            'original_price' => 800,
            'new_price' => 1200,
            'price_delta' => 400,
            'status' => DateChangeStatus::AwaitingPayment->value,
        ]);
        // Make it stale.
        DB::table('booking_date_change_requests')->where('id', $request->id)
            ->update(['updated_at' => now()->subHours(2)]);

        $expired = $this->service->expireStale(30);

        $this->assertGreaterThanOrEqual(1, $expired);
        $this->assertSame(DateChangeStatus::Rejected->value, $request->fresh()->status);
    }

    public function test_apply_dates_rolls_back_local_change_when_ownerrez_sync_fails(): void
    {
        // Skip the OwnerRez availability call; we only want the outbound date PATCH to fail.
        config(['ownerrez.availability.enabled' => false]);

        // Force the OwnerRez date PATCH to throw.
        $this->instance(OwnerRezApiService::class, \Mockery::mock(OwnerRezApiService::class, function ($m) {
            $m->shouldReceive('updateBooking')->andThrow(new OwnerRezApiException('sync failed', '/v2/bookings/1', [], 500));
        }));

        $booking = $this->createBooking();
        OwnerRezPropertyMapping::create([
            'apartment_id' => $booking->apartment_id,
            'ownerrez_property_id' => '999888777',
            'ownerrez_property_name' => 'RB-Test',
            'sync_enabled' => true,
            'check_availability_enabled' => false,
        ]);
        $booking->update(['ownerrez_booking_id' => 555444333]);

        $originalIn = $booking->check_in->toDateString();
        $originalOut = $booking->check_out->toDateString();

        $request = DateChangeRequest::create([
            'booking_id' => $booking->id,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'new_check_in' => $booking->check_in->copy()->addDays(7),
            'new_check_out' => $booking->check_out->copy()->addDays(7),
            'original_price' => 800,
            'new_price' => 800,
            'price_delta' => 0,
            'status' => DateChangeStatus::PendingReview->value,
        ]);

        $threw = false;
        try {
            $this->service->applyDates($request);
        } catch (\Throwable $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'OwnerRez sync failure should bubble up');

        // Rollback: the local booking must be untouched and the request not marked applied.
        $booking->refresh();
        $this->assertSame($originalIn, $booking->check_in->toDateString());
        $this->assertSame($originalOut, $booking->check_out->toDateString());
        $this->assertNull($request->fresh()->applied_at);
    }

    private function createBooking(): Booking
    {
        $apartment = Apartment::create([
            'name_ar' => 'شقة اختبار التعديل',
            'name_en' => 'Date Change Test Apartment',
            'num_rooms' => 2,
            'num_beds' => 2,
            'area' => 80,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $this->createdApartmentIds[] = $apartment->id;

        $customer = Customer::forceCreate([
            'first_name' => 'Date',
            'last_name' => 'Change',
            'email' => 'date.change.'.uniqid().'@example.com',
            'phone' => '+9660'.mt_rand(100000000, 999999999),
            'account_verified' => false,
        ]);
        $this->createdCustomerIds[] = $customer->id;

        // Create as approved+pending (no BookingApproved event), then flip payment only.
        $checkIn = now()->addMonths(3)->startOfDay();
        $booking = Booking::create([
            'apartment_id' => $apartment->id,
            'customer_id' => $customer->id,
            'customer_full_name' => 'Date Change',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->copy()->addDays(4)->toDateString(),
            'number_of_nights' => 4,
            'adults_count' => 2,
            'children_count' => 0,
            'total_price' => 800.00,
            'final_price' => 800.00,
            'one_night_price' => 200.00,
            'tax' => 0,
            'status' => 'approved',
            'payment_status' => 'pending',
            'booking_source' => 'web',
            'is_airbnb_booking' => 0,
        ]);
        $booking->update(['payment_status' => 'paid']); // status unchanged → no event
        $this->createdBookingIds[] = $booking->id;

        return $booking->fresh();
    }
}
