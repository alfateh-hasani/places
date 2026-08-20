<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a customer's cancellation request is accepted (finalized) — either via the
 * OwnerRez cancel webhook or the admin "Approve & Refund" action. Phase 2 attaches the
 * refund listener; in Phase 1 it is a no-op seam.
 */
class CustomerCancellationAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}
}
