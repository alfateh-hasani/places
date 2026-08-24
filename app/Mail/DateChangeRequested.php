<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\DateChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DateChangeRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public DateChangeRequest $dateChangeRequest,
    ) {}

    public function build(): self
    {
        return $this->subject('طلب تعديل تواريخ حجز - '.$this->booking->number_of_booking)
            ->view('emails.date_change_requested')
            ->with([
                'booking' => $this->booking,
                'request' => $this->dateChangeRequest,
            ]);
    }
}
