<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\PaymentMethods\GeideaPayment;
use Tests\TestCase;

/**
 * A paid Geidea order may only confirm the transaction it was created for. Without this,
 * one paid orderId could be replayed on the unauthenticated callback to approve any
 * booking (and issue its smart-lock passcode).
 *
 * Order payloads omit orderId so the duplicate-order DB lookup is skipped and the
 * matching rules are tested in isolation.
 */
class GeideaOrderOwnershipTest extends TestCase
{
    private function transaction(): Transaction
    {
        return (new Transaction)->forceFill([
            'id' => 501,
            'transaction_reference' => 'ref-2026-000501',
            'amount' => 450.00,
            'currency' => 'SAR',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{order: array<string, mixed>}
     */
    private function order(array $overrides = []): array
    {
        return ['order' => array_merge([
            'detailedStatus' => 'Paid',
            'merchantReferenceId' => 'ref-2026-000501',
            'amount' => 450.00,
            'currency' => 'SAR',
        ], $overrides)];
    }

    public function test_paid_order_created_for_the_transaction_is_accepted(): void
    {
        $this->assertTrue((new GeideaPayment)->isPaidForTransaction($this->order(), $this->transaction()));
    }

    public function test_order_for_another_transaction_is_rejected(): void
    {
        $order = $this->order(['merchantReferenceId' => 'ref-2026-000100', 'amount' => 125.50]);

        $this->assertFalse((new GeideaPayment)->isPaidForTransaction($order, $this->transaction()));
    }

    public function test_order_without_merchant_reference_is_rejected(): void
    {
        $order = $this->order(['merchantReferenceId' => null]);

        $this->assertFalse((new GeideaPayment)->isPaidForTransaction($order, $this->transaction()));
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        $this->assertFalse((new GeideaPayment)->isPaidForTransaction($this->order(['amount' => 1.00]), $this->transaction()));
    }

    public function test_currency_mismatch_is_rejected(): void
    {
        $this->assertFalse((new GeideaPayment)->isPaidForTransaction($this->order(['currency' => 'USD']), $this->transaction()));
    }

    public function test_unpaid_order_is_rejected(): void
    {
        $this->assertFalse((new GeideaPayment)->isPaidForTransaction($this->order(['detailedStatus' => 'Failed']), $this->transaction()));
    }

    public function test_failed_lookup_is_rejected(): void
    {
        $this->assertFalse((new GeideaPayment)->isPaidForTransaction(false, $this->transaction()));
    }
}
