<?php

namespace App\Services\BookingUnitTransfer;

use App\Enums\TransferDirection;
use App\Models\Apartment;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Config;

/**
 * Prices a unit transfer over the booking's (unchanged) dates and derives the exact price
 * columns to write on the booking, honouring the agreed money policy:
 *
 *   - Destination costs the SAME  → no money movement.
 *   - Destination costs MORE      → the difference is absorbed as a system discount; the
 *                                   customer keeps paying exactly what they already paid
 *                                   (final_price unchanged, no charge).
 *   - Destination costs LESS      → the booking is re-priced down and the difference is
 *                                   refunded to the customer by staff from the dashboard.
 *
 * The destination is priced at its NORMAL (rack) rate — the customer's coupon is NOT re-applied.
 * The coupon was a promo tied to the original unit and is already reflected in what they paid;
 * re-applying it to a different unit would double-count the discount. (Date change keeps the
 * coupon because it stays on the same unit.) The customer is never charged more, so this never
 * penalizes them — it only means a cheaper-unit refund is the true rack difference.
 *
 * In every branch the invariant `final_price = total_price - discount` holds, so existing
 * reports stay consistent.
 */
class TransferPricingCalculator
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function quote(Booking $booking, Apartment $destination): UnitTransferQuote
    {
        $checkIn = $booking->check_in;
        $checkOut = $booking->check_out;
        $nights = (int) $this->bookingService->calculateNumberOfNights($checkIn, $checkOut);

        // Rack rate — no coupon (see class docblock). With no coupon, gross == final for the
        // destination; any discount below is purely the transfer concession (absorbed surcharge).
        $destPrices = $this->bookingService->calculatePricesWithDates($destination, $checkIn, $checkOut, null);
        $destGross = round((float) $destPrices['total_price'], 2); // VAT-inclusive rack price
        $destFinal = round((float) $destPrices['final_price'], 2); // == rack price (no coupon)

        $original = round((float) $booking->final_price, 2);       // what the customer actually paid
        $delta = round($destFinal - $original, 2);
        $direction = TransferDirection::fromDelta($delta);

        $vatRate = (float) Config::get('settings.vat_rate', 15);
        $oneNight = $nights > 0 ? round($destGross / $nights, 2) : $destGross;

        // Absorb (more expensive) keeps the charged price at the original; refund/even re-price
        // to the destination's own final. Discount always balances the invariant.
        $charged = $direction === TransferDirection::Surcharge ? $original : $destFinal;
        $discount = round($destGross - $charged, 2);
        $tax = round($charged * $vatRate / (100 + $vatRate), 2);

        $refundAmount = $direction === TransferDirection::Refund ? round($original - $destFinal, 2) : 0.0;

        return new UnitTransferQuote(
            destination: $destination,
            nights: $nights,
            originalPrice: $original,
            newPrice: $destFinal,
            priceDelta: $delta,
            direction: $direction,
            refundAmount: max($refundAmount, 0.0),
            bookingPrices: [
                'total_price' => $destGross,
                'final_price' => $charged,
                'discount' => max($discount, 0.0),
                'one_night_price' => $oneNight,
                'tax' => $tax,
            ],
        );
    }
}
