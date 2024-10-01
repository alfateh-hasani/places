<?php

namespace Database\Seeders;

use App\Models\Advantage;
use App\Models\Policy;
use App\Models\Slider;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    //php artisan db:seed --class=TestSeeder
    public function run(): void
    {
//        $policies = [
//            [
//                'name_ar' => 'سياسة الإلغاء',
//                'name_en' => 'Cancellation Policy',
//                'description_ar' => 'تفاصيل حول الشروط والأحكام لإلغاء حجوزات الشقق.',
//                'description_en' => 'Details about the terms and conditions for canceling apartment bookings.',
//            ],
//            [
//                'name_ar' => 'سياسة الأطفال',
//                'name_en' => 'Children Policy',
//                'description_ar' => 'معلومات بخصوص إقامة الأطفال في الشقق والشروط المتعلقة بذلك.',
//                'description_en' => 'Information regarding the accommodation of children in the apartments and related conditions.',
//            ],
//            [
//                'name_ar' => 'سياسة الحيوانات الأليفة',
//                'name_en' => 'Pets Policy',
//                'description_ar' => 'إرشادات حول السماح بالحيوانات الأليفة في الشقق والقواعد المرتبطة بها.',
//                'description_en' => 'Guidelines on allowing pets in the apartments and the associated rules.',
//            ],
//            [
//                'name_ar' => 'سياسة الدفع',
//                'name_en' => 'Payment Policy',
//                'description_ar' => 'شرح لطرق الدفع المقبولة وجدول الدفعات لاستئجار الشقق.',
//                'description_en' => 'Explanation of accepted payment methods and the payment schedule for apartment rentals.',
//            ],
//            [
//                'name_ar' => 'سياسة الإضافات',
//                'name_en' => 'Add-ons Policy',
//                'description_ar' => 'معلومات حول الخدمات أو المرافق الإضافية المتاحة مع استئجار الشقة.',
//                'description_en' => 'Information about additional services or amenities available with the apartment rental.',
//            ],
//        ];
//
//        foreach ($policies as $policy) {
//            Policy::create($policy);
//        }

//        $advantages = [
//            [
//                'name_ar' => 'موقع مركزي',
//                'name_en' => 'Central Location',
//                'description_ar' => 'تقع الشقق في موقع مركزي بالقرب من المعالم السياحية الرئيسية.',
//                'description_en' => 'The apartments are centrally located near major tourist attractions.',
//
//            ],
//            [
//                'name_ar' => 'خدمة الغرف',
//                'name_en' => 'Room Service',
//                'description_ar' => 'تتوفر خدمة الغرف على مدار الساعة لراحة النزلاء.',
//                'description_en' => 'Room service is available 24/7 for the convenience of guests.',
//
//            ],
//            [
//                'name_ar' => 'مواقف مجانية',
//                'name_en' => 'Free Parking',
//                'description_ar' => 'تتوفر مواقف مجانية للسيارات للنزلاء في الموقع.',
//                'description_en' => 'Free parking is available for guests on site.',
//
//            ],
//            [
//                'name_ar' => 'مسبح خارجي',
//                'name_en' => 'Outdoor Pool',
//                'description_ar' => 'يتميز الموقع بمسبح خارجي للاستمتاع بالسباحة في الهواء الطلق.',
//                'description_en' => 'The site features an outdoor pool for swimming outdoors.',
//
//            ],
//        ];
//
//        foreach ($advantages as $advantage) {
//            Advantage::create($advantage);
//        }

        $sliders = [
            [
                'name_ar' => 'الشقق الفاخرة',
                'name_en' => 'Luxury Apartments',

            ],
            [
                'name_ar' => 'المسبح الخارجي',
                'name_en' => 'Outdoor Pool',

            ],
            [
                'name_ar' => 'الموقع المركزي',
                'name_en' => 'Central Location',

            ],
            [
                'name_ar' => 'المواقف المجانية',
                'name_en' => 'Free Parking',

            ],
        ];

        foreach ($sliders as $slider) {
            Slider::create($slider);
        }
    }
}
