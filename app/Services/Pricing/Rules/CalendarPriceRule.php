<?php 
namespace App\Services\Pricing\Rules;

use App\Models\ApartmentPriceCalendar;

class CalendarPriceRule implements PriceRuleInterface
{
    public function apply(array $context): array
    {
        $apartment = $context['apartment'];
        $date      = $context['date']->toDateString();

        $cal = ApartmentPriceCalendar::where('apartment_id', $apartment->id)
              ->where('date', $date)->first();

        // فقط إذا كان هناك سعر مخصص في التقويم
        if ($cal && $cal->custom_price) {
            $context['current_price'] = (float) $cal->custom_price;
        }

        return $context;
    }
}
