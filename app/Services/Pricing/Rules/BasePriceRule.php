<?php 
namespace App\Services\Pricing\Rules;

class BasePriceRule implements PriceRuleInterface
{
    public function apply(array $context): array
    {
        $pricing = $context['apartment']->pricing;
        
        // محاولة الحصول على السعر من pricing أولاً، ثم من apartment
        $basePrice = 0.0;
        
        if ($pricing && isset($pricing->base_price) && $pricing->base_price > 0) {
            $basePrice = (float) $pricing->base_price;
        } elseif (isset($context['apartment']->price) && $context['apartment']->price > 0) {
            $basePrice = (float) $context['apartment']->price;
        }
        
        $context['current_price'] = $basePrice;
        
        // تسجيل تحذير إذا كان السعر 0
        if ($basePrice == 0) {
            \Log::warning('BasePriceRule: السعر الأساسي للشقة يساوي 0', [
                'apartment_id' => $context['apartment']->id,
                'has_pricing' => !is_null($pricing),
                'base_price' => $pricing->base_price ?? null,
                'apartment_price' => $context['apartment']->price ?? null,
            ]);
        }
        
        return $context;
    }
}
