<?php

namespace App\Listeners;

use App\Events\CustomerCancellationAccepted;
use App\Jobs\ProcessGeideaRefundJob;

class ProcessCustomerRefund
{
    /**
     * When a customer's cancellation is accepted, kick off the automated Geidea refund.
     */
    public function handle(CustomerCancellationAccepted $event): void
    {
        ProcessGeideaRefundJob::dispatch($event->booking->id);
    }
}
