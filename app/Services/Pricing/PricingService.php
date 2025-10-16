<?php

namespace App\Services\Pricing;

use App\Models\Apartment;
use Illuminate\Pipeline\Pipeline;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Closure;
use App\Services\Pricing\Rules\{
    BasePriceRule,
    CalendarPriceRule,
    DayOfWeekPriceRule,
    SeasonalPriceRule,
    WeekendPriceRule
};
 
class PricingService
{
    protected array $rules = [
        BasePriceRule::class,
        WeekendPriceRule::class,
        CalendarPriceRule::class, // يجب أن يكون آخر قاعدة ليكون له الأولوية القصوى
    ];

    /**
     * @param  Apartment  $apartment
     * @param  Carbon     $checkIn
     * @param  Carbon     $checkOut
     * @return array{
     *     total: float,          // السعر النهائي شاملة الخصم (للاستخدام المباشر)
     *     discount: float,       // قيمة الخصم المطبّق
     *     final: float,          // نفس القيمة كما “final” (مكررة للوضوح)
     *     vat: float,            // قيمة الضريبة ضمن السعر
     *     net: float,            // السعر بدون ضريبة
     *     one_night_price: float // سعر أول ليلة
     * }
     */
    public function calculate(Apartment $apartment, Carbon $checkIn, Carbon $checkOut): array
    {
        $nights   = $checkIn->diffInDays($checkOut);
        $subtotal = 0.0;
        $discount = 0.0;
        $oneNightPrice = 0.0;

        // Build the pipeline closures from rule classes
        $pipes = array_map(function(string $ruleClass): Closure {
            return function(array $context, Closure $next) use ($ruleClass) {
                $rule = app($ruleClass);
                if (method_exists($rule, 'apply')) {
                    $context = $rule->apply($context);
                }
                return $next($context);
            };
        }, $this->rules);

        // حساب الأسعار اللي ليلة بليلة
        for ($day = 0; $day < $nights; $day++) {
            $date = $checkIn->copy()->addDays($day);

            $context = [
                'apartment'     => $apartment,
                'date'          => $date,
                'current_price' => 0.0,
                'discount'      => 0.0,
                'total_nights'  => $nights,
            ];

            $context = app(Pipeline::class)
                ->send($context)
                ->through($pipes)
                ->thenReturn();

            $priceForNight = $context['current_price'];
           
            $subtotal     += $priceForNight;
            $discount     += $context['discount'];

            if ($day === 0) {
                $oneNightPrice = $priceForNight;
            }
        }

        // تطبيق خصم الإقامة الطويلة
        $pricing = $apartment->pricing;
        if ($pricing
            && $pricing->long_stay_discount !== null
            && $nights >= 7
        ) {
            $discount += round($subtotal * ($pricing->long_stay_discount / 100), 2);
        }

        // حسبة السعر النهائي (شامل الضريبة)
        $final   = max($subtotal - $discount, 0.0);
        $vatRate = Config::get('settings.vat_rate', 15);
        $vat     = round($final * $vatRate / (100 + $vatRate), 2);
        $net     = round($final - $vat, 2);
        
        // حساب متوسط سعر الليلة (بعد الخصم)
        $avgPricePerNight = $nights > 0 ? round($final / $nights, 2) : $oneNightPrice;

        return [
            // استخدم دائمًا "total" للسعر النهائي بعد الخصم (شامل الضريبة)
            'total'           => round($final, 2),
            'total_price'     => round($final, 2),  // alias لـ total للتوافق
            'discount'        => round($discount, 2),
            'final'           => round($final, 2),
            'vat'             => $vat,               // الضريبة المستخرجة من السعر
            'net'             => $net,               // السعر بدون ضريبة
            'one_night_price' => $avgPricePerNight,  // متوسط سعر الليلة بعد الخصم
            'breakdown'       => $breakdown ?? [],   // تفاصيل السعر لكل ليلة
        ];
    }
}
