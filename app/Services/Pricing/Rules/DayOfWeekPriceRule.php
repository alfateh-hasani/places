<?php 
namespace App\Services\Pricing\Rules;

use App\Models\DayOfWeekPricing;

class DayOfWeekPriceRule implements PriceRuleInterface
{
    public function apply(array $context): array
    {
        $dow = $context['date']->dayOfWeek;
        $price = DayOfWeekPricing::where('apartment_id', $context['apartment']->id)
                 ->where('day_of_week', $dow)->value('price');

        if ($price !== null) {
            $context['current_price'] = (float) $price;
        }

        return $context;
    }
}
