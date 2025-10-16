<?php 
namespace App\Services\Pricing\Rules;

use App\Models\SeasonalPricing;

class SeasonalPriceRule implements PriceRuleInterface
{
    public function apply(array $context): array
    {
        $date = $context['date'];
        $season = SeasonalPricing::where(function($q) use ($date) {
                        $q->whereNull('apartment_id')
                          ->orWhere('apartment_id', $context['apartment']->id);
                    })
                    ->where('start_date','<=',$date)
                    ->where('end_date','>=',$date)
                    ->orderByDesc('multiplier')
                    ->first();

        if ($season) {
            $context['current_price'] *= (float) $season->multiplier;
        }

        return $context;
    }
}
