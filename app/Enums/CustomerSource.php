<?php

namespace App\Enums;

/**
 * Where a Customer account originated.
 *
 *   Local     → registered directly through our own app/web (registerUser).
 *   OwnerRez  → auto-created because an inbound channel booking (Airbnb, Booking.com,
 *               ...) arrived via OwnerRez for a phone/guest we didn't already have.
 */
enum CustomerSource: string
{
    case Local = 'local';
    case OwnerRez = 'ownerrez';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'محلي',
            self::OwnerRez => 'OwnerRez',
        };
    }
}
