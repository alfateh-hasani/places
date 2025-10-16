<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Sabre\VObject\Reader;


use App\Models\Apartment;
use App\Models\Booking;
use Carbon\Carbon;

class ImportAirbnbICS extends Command
{
    protected $signature = 'import:airbnb-ics';
    protected $description = 'Import Airbnb ICS for all apartments that have ics_url set';

    public function handle()
    {

       
        $apartments = Apartment::whereNotNull('ics_url')->get();
        

        if ($apartments->isEmpty()) {
            $this->info("No apartments with ICS URL found.");
            return 0;
        }

        foreach ($apartments as $apartment) {
            $icsUrl = $apartment->ics_url;
            if(!$icsUrl){
                continue;
            }
            
            $this->info("Fetching ICS from {$icsUrl} for apartment ID: {$apartment->id}");
            //$icsUrl = rawurlencode($icsUrl);
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($icsUrl);
            
             
            if ($response->failed()) {
                $this->error("Failed to fetch ICS file for apartment {$apartment->id}");
                continue;
            }

            $icsContent = $response->body();

            try {
                // قراءة محتوى ملف ICS وتحليله
                $vcalendar = Reader::read($icsContent);
            
                foreach ($vcalendar->VEVENT as $event) {
                    $start = $event->DTSTART->getDateTime();
                    $end = $event->DTEND->getDateTime();
                    $uid = (string) $event->UID;
                    
                    // إنشاء معرف فريد من مزيج المعرفات (بدون الاعتماد على UID)
                    $uniqueId = 'airbnb_' . $apartment->id . '_' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d');
            
                    // تحقق إذا كان الحجز موجودًا بالفعل باستخدام المعرف الفريد
                    $exists = Booking::where('apartment_id', $apartment->id)
                        ->where('number_of_booking', $uniqueId)
                        ->exists();
                    
                    // تحقق إضافي من عدم وجود حجوزات متداخلة في نفس التواريخ
                    $overlappingExists = Booking::where('apartment_id', $apartment->id)
                        ->where('check_in', '<=', $end->format('Y-m-d'))
                        ->where('check_out', '>=', $start->format('Y-m-d'))
                        ->where('status', '!=', 'cancelled')
                        ->exists();
            
                    if (!$exists && !$overlappingExists) {
                        Booking::create([
                            'number_of_booking'   =>  $uniqueId,
                            'customer_full_name'  => 'External Airbnb Booking',
                            'customer_email'      => 'airbnb@gmail.com',
                            'apartment_id'        => $apartment->id,
                            'check_in'            => $start->format('Y-m-d'),
                            'check_out'           => $end->format('Y-m-d'),
                            'total_price'         => 0,
                            'final_price'         => 0,
                            'number_of_nights'    => $start->diff($end)->days,
                            'adults_count'        => 0,
                            'children_count'      => 0,
                            'status'              => 'booked',
                            'payment_status'      => 'paid',
                            'payment_method_code' => 'airbnb',
                            'created_at'          => now(),
                            'updated_at'          => now(),
                            'is_airbnb_booking'   => 1,
                            'ics_url'              => $icsUrl,
                        ]);
            
                        $this->info("Imported Airbnb booking for apartment {$apartment->id}: {$start->format('Y-m-d')} - {$end->format('Y-m-d')}");
                    } else {
                        if ($exists) {
                            $this->warn("Skipped duplicate booking for apartment {$apartment->id}: {$start->format('Y-m-d')} - {$end->format('Y-m-d')} (already exists)");
                        } elseif ($overlappingExists) {
                            $this->warn("Skipped overlapping booking for apartment {$apartment->id}: {$start->format('Y-m-d')} - {$end->format('Y-m-d')} (dates overlap with existing booking)");
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Failed to parse ICS file for apartment {$apartment->id}: " . $e->getMessage());
                continue;
            }
            

            sleep(3); 
        }

        return 0;
    }
}
