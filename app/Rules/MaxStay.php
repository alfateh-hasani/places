<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Applied to the check-out field: the stay may not exceed config('booking.max_nights').
 * Per-night pricing makes unbounded ranges a cheap way to exhaust PHP workers.
 */
class MaxStay implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(private readonly string $checkInField = 'check_in') {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $checkIn = Carbon::parse((string) ($this->data[$this->checkInField] ?? ''));
            $checkOut = Carbon::parse((string) $value);
        } catch (\Throwable) {
            return; // the field's own `date` rule reports malformed dates
        }

        $maxNights = (int) config('booking.max_nights', 365);

        if ($checkIn->diffInDays($checkOut, false) > $maxNights) {
            $fail(__('site.max_stay_exceeded', ['nights' => $maxNights]));
        }
    }
}
