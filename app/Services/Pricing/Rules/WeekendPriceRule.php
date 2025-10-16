<?php 
namespace App\Services\Pricing\Rules;

use Carbon\Carbon;

class WeekendPriceRule implements PriceRuleInterface
{
    public function apply(array $context): array
    {
        $dow = $context['date']->dayOfWeek;
        
        // تطبيق سعر نهاية الأسبوع للخميس والجمعة والسبت
        if (in_array($dow, [Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY])) {
            $pricing = $context['apartment']->pricing;
            
            // تحقق من وجود سعر نهاية الأسبوع
            if ($pricing && $pricing->weekend_price) {
                $context['current_price'] = (float) $pricing->weekend_price;
            }
        }
        return $context;
    }
}
