<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BlockedCustomerBookingSynced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Customer $customer,
    ) {}

    public function build(): self
    {
        return $this->subject('حجز وارد من قناة خارجية لعميل محظور - '.$this->booking->number_of_booking)
            ->view('emails.blocked_customer_booking_synced')
            ->with([
                'booking' => $this->booking,
                'customer' => $this->customer,
            ]);
    }
}
