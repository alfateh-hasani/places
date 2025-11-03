<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCanceled extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $data['booking'] = $this->booking;
        return $this->subject('إلغاء حجز - ' . $this->booking->number_of_booking)
                    ->view('emails.booking_canceled')
                    ->with($data);
    }
}
