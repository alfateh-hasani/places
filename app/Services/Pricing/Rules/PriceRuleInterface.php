<?php 
namespace App\Services\Pricing\Rules;

interface PriceRuleInterface
{
    /**
     * @param  array  $context   محتوى مثل ['apartment'=>Model,'date'=>Carbon, 'current_price'=>float]
     * @return array    يُعيد $context محدثًا بـ ['current_price'=>float,'discount'=>float]
     */
    public function apply(array $context): array;
}
