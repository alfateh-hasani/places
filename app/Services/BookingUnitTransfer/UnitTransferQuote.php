<?php

namespace App\Services\BookingUnitTransfer;

use App\Enums\TransferDirection;
use App\Models\Apartment;

/**
 * Immutable result of pricing a proposed unit transfer over the booking's (unchanged) dates.
 *
 * @property-read array{total_price:float,final_price:float,discount:float,one_night_price:float,tax:float} $bookingPrices
 */
final class UnitTransferQuote
{
    /**
     * @param  array{total_price:float,final_price:float,discount:float,one_night_price:float,tax:float}  $bookingPrices
     *         The exact price columns to write on the booking when the move is applied.
     */
    public function __construct(
        public readonly Apartment $destination,
        public readonly int $nights,
        public readonly float $originalPrice,
        public readonly float $newPrice,
        public readonly float $priceDelta,
        public readonly TransferDirection $direction,
        public readonly float $refundAmount,
        public readonly array $bookingPrices,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'destination_id' => $this->destination->id,
            'nights' => $this->nights,
            'original_price' => $this->originalPrice,
            'new_price' => $this->newPrice,
            'price_delta' => $this->priceDelta,
            'direction' => $this->direction->value,
            'refund_amount' => $this->refundAmount,
        ];
    }
}
