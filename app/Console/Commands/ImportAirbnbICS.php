<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Throwable;

class ImportAirbnbICS extends Command
{
    protected $signature   = 'import:airbnb-ics {--apartment_id=* : Limit to specific apartment ids} {--dry-run : Parse only, do not write}';
    protected $description = 'Import Airbnb ICS with idempotent upsert by external_reservation_id, end-exclusive dates, adoption of internal matches, and conflict logging.';

    private const UA                  = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) ICSImporter/1.0 Chrome/120 Safari/537.36';
    private const HTTP_TIMEOUT_SEC    = 20;
    private const HTTP_RETRIES        = 2;
    private const HTTP_RETRY_DELAY_MS = 500;
    private const PER_APARTMENT_SLEEP = 1; // seconds

    public function handle(): int
    {
        $ids = array_filter((array)$this->option('apartment_id'));
        $q   = Apartment::query()->whereNotNull('ics_url');

        if ($ids) {
            $q->whereIn('id', $ids);
        }

        $apartments = $q->orderBy('id')->get(['id', 'ics_url']);

        if ($apartments->isEmpty()) {
            $this->info('No apartments with ICS URL found.');
            return self::SUCCESS;
        }

        $this->info("Found {$apartments->count()} apartment(s) with ICS URL.");

        foreach ($apartments as $apartment) {

            if(isset($apartment->ownerrezMapping->ownerrez_property_id) && $apartment->ownerrezMapping->ownerrez_property_id !== null) {
                continue;
            }
            
            $icsUrl = trim((string)$apartment->ics_url);
            if ($icsUrl === '') {
                continue;
            }

            $this->line("→ Fetching ICS for apartment #{$apartment->id}");

            try {
                $icsContent = $this->fetchIcs($apartment->id, $icsUrl);
                if ($icsContent === null) {
                    sleep(self::PER_APARTMENT_SLEEP);
                    continue;
                }

                $vcal   = Reader::read($icsContent);
                $events = iterator_to_array($vcal->VEVENT ?? []);
                $this->line('   Parsed events: ' . count($events));

                // حذف جميع حجوزات Airbnb القديمة من نفس المصدر قبل الاستيراد
                if (!(bool)$this->option('dry-run')) {
                    $deleted = $this->deleteOldAirbnbBookings($apartment->id, $icsUrl);
                    if ($deleted > 0) {
                        $this->line("   🗑️  Deleted {$deleted} old Airbnb booking(s)");
                    }
                }

                foreach ($events as $event) {
                    /** @var VEvent $event */
                    $this->processEvent($apartment->id, $icsUrl, $event, (bool)$this->option('dry-run'));
                }
            } catch (Throwable $e) {
                $this->error("   Failed for apartment #{$apartment->id}: {$e->getMessage()}");
                Log::warning('airbnb_ics.import_failed', [
                    'apartment_id' => $apartment->id,
                    'error'        => $e->getMessage(),
                ]);
            }

            sleep(self::PER_APARTMENT_SLEEP);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function fetchIcs(int $apartmentId, string $url): ?string
    {
        $headers = ['User-Agent' => self::UA];

        $etagCol = Schema::hasColumn('apartments', 'airbnb_etag');
        $lmCol   = Schema::hasColumn('apartments', 'airbnb_last_modified');

        if ($etagCol || $lmCol) {
            $ap = Apartment::query()->find($apartmentId, ['id', 'airbnb_etag', 'airbnb_last_modified']);
            if ($ap) {
                if ($etagCol && !empty($ap->airbnb_etag)) {
                    $headers['If-None-Match'] = $ap->airbnb_etag;
                }
                if ($lmCol && !empty($ap->airbnb_last_modified)) {
                    $headers['If-Modified-Since'] = gmdate('D, d M Y H:i:s T', strtotime((string)$ap->airbnb_last_modified));
                }
            }
        }

        $resp = Http::withHeaders($headers)
            ->timeout(self::HTTP_TIMEOUT_SEC)
            ->retry(self::HTTP_RETRIES, self::HTTP_RETRY_DELAY_MS, throw: false)
            ->get($url);

        if ($resp->status() === 304) {
            $this->line('   Not modified (304).');
            return null;
        }

        if ($resp->failed()) {
            $this->error("   HTTP {$resp->status()} fetching ICS.");
            return null;
        }

        if (($etagCol || $lmCol) && ($resp->hasHeader('ETag') || $resp->hasHeader('Last-Modified'))) {
            $updates = [];
            if ($etagCol && $resp->hasHeader('ETag')) {
                $updates['airbnb_etag'] = $resp->header('ETag');
            }
            if ($lmCol && $resp->hasHeader('Last-Modified')) {
                $updates['airbnb_last_modified'] = Carbon::parse($resp->header('Last-Modified'));
            }
            if ($updates) {
                Apartment::query()->whereKey($apartmentId)->update($updates);
            }
        }

        return $resp->body();
    }

    private function processEvent(int $apartmentId, string $icsUrl, VEvent $event, bool $dryRun = false): void
    {
        $uid   = (string)($event->UID ?? '');
        $resId = $this->extractReservationId($event);

        $start = $event->DTSTART->getDateTime();
        $end   = isset($event->DTEND) ? $event->DTEND->getDateTime() : (clone $start)->modify('+1 day');

        // DTEND exclusive
        $checkIn  = Carbon::instance($start)->toDateString();
        $checkOut = Carbon::instance($end)->toDateString();
        if ($checkOut <= $checkIn) {
            $checkOut = Carbon::instance($start)->addDay()->toDateString();
        }

        $status   = strtoupper((string)($event->STATUS ?? ''));
        $sequence = (int)($event->SEQUENCE ?? 0);

        if ($dryRun) {
            $this->line("   [DRY] RES={$resId} UID={$uid} {$checkIn} → {$checkOut} status={$status} seq={$sequence}");
            return;
        }

        // تجاهل الحجوزات الملغية - تم حذفها مسبقاً
        if ($status === 'CANCELLED') {
            return;
        }

        DB::transaction(function () use ($apartmentId, $uid, $resId, $sequence, $checkIn, $checkOut, $icsUrl) {
            // التحقق من التعارضات مع مصادر أخرى (ليس Airbnb من نفس المصدر)
            $overlapping = Booking::query()
                ->where('apartment_id', $apartmentId)
                ->whereIn('status', ['booked', 'approved', 'pending'])
                ->where(function ($q) use ($checkIn, $checkOut) {
                    $q->where('check_in', '<', $checkOut)
                      ->where('check_out', '>', $checkIn);
                })
                ->lockForUpdate()
                ->get();

            // البحث عن حجز داخلي يمكن تبنيه (نفس التواريخ، بدون معرّف خارجي)
            $adoptCandidate = $overlapping->first(function ($b) use ($checkIn, $checkOut) {
                return $b->check_in === $checkIn
                    && $b->check_out === $checkOut
                    && empty($b->external_reservation_id)
                    && empty($b->external_uid)
                    && !$b->is_airbnb_booking;
            });

            if ($adoptCandidate) {
                // تبني الحجز الداخلي وتحويله لحجز Airbnb
                $adoptCandidate->update([
                    'external_reservation_id' => $resId,
                    'external_uid'            => $uid ?: null,
                    'external_sequence'       => $sequence,
                    'number_of_booking'       => "airbnb_{$apartmentId}_{$checkIn}_{$checkOut}",
                    'payment_method_code'     => $adoptCandidate->payment_method_code ?: 'airbnb',
                    'ics_url'                 => $icsUrl,
                    'is_airbnb_booking'       => true,
                    'updated_at'              => now(),
                ]);
                $this->line("   ✓ Adopted internal booking id={$adoptCandidate->id} → RES={$resId} {$checkIn} → {$checkOut}");
                $this->cleanupConflicts($apartmentId, $resId, $uid);
                return;
            }

            // إذا وُجد تعارض حقيقي مع مصدر آخر
            if ($overlapping->isNotEmpty()) {
                $this->upsertConflict($apartmentId, $resId, $uid, $checkIn, $checkOut, $overlapping->first()->id, [
                    'ics_url'  => $icsUrl,
                    'sequence' => $sequence,
                    'overlaps' => $overlapping->pluck('id')->toArray(),
                    'reason'   => 'overlap-with-other-source',
                ]);
                $this->warn("   ⚠️  Channel conflict RES={$resId} {$checkIn} → {$checkOut}");
                return;
            }

            // إدراج حجز Airbnb جديد
            Booking::create([
                'external_reservation_id' => $resId,
                'external_uid'            => $uid ?: null,
                'external_sequence'       => $sequence,
                'number_of_booking'       => "airbnb_{$apartmentId}_{$checkIn}_{$checkOut}",
                'customer_full_name'      => 'External Airbnb Booking',
                'customer_email'          => 'airbnb@gmail.com',
                'apartment_id'            => $apartmentId,
                'check_in'                => $checkIn,
                'check_out'               => $checkOut,
                'total_price'             => 0,
                'final_price'             => 0,
                'number_of_nights'        => Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)),
                'adults_count'            => 0,
                'children_count'          => 0,
                'status'                  => 'booked',
                'payment_status'          => 'paid',
                'payment_method_code'     => 'airbnb',
                'is_airbnb_booking'       => true,
                'ics_url'                 => $icsUrl,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            $this->line("   ✓ Inserted RES={$resId} {$checkIn} → {$checkOut}");
            $this->cleanupConflicts($apartmentId, $resId, $uid);
        });
    }

    private function extractReservationId(VEvent $event): ?string
{
    // 1) وصف الحدث (قد يحتوي على folding: CRLF + space/tab)
    $desc = '';
    if (isset($event->DESCRIPTION)) {
        // getValue() من Sabre تفك الترميز لكن لا تعتمد عليها؛ طبّق normalization بنفسك
        $desc = (string)$event->DESCRIPTION;
    }
    // إزالة طيّ الأسطر وفق RFC 5545: CRLF ثم space/tab
    $norm = preg_replace('/\r?\n[ \t]/', '', $desc ?? '');
    // إزالة المسافات الإضافية التي قد تقطع كلمة details
    $norm = preg_replace('/\s+/', ' ', $norm);

    // 2) محاولات متعددة للاستخراج
    $patterns = [
        '~/(?:hosting/)?reservations/(?:de)?tails/([A-Z0-9]{6,})~i', // details/XXXXXXXX
        '~/(?:itinerary|trips)/([A-Z0-9]{6,})~i',                    // itinerary/XXXXXXXX
        '~\bReservation\s*Code:\s*([A-Z0-9]{6,})\b~i',               // نصيّة: Reservation Code: XXXXX
        '~\breservationId=([A-Z0-9]{6,})\b~i',                       // كـ query param
    ];
    foreach ($patterns as $re) {
        if (preg_match($re, $norm, $m)) {
            return strtoupper($m[1]);
        }
    }

    // 3) محاولة من UID إذا تضمن الرمز
    $uid = (string)($event->UID ?? '');
    if ($uid && preg_match('~([A-Z0-9]{6,})@airbnb\.com$~i', $uid, $m)) {
        return strtoupper($m[1]);
    }

    return null;
}


    private function upsertConflict(
        int $apartmentId,
        ?string $resId,
        ?string $uid,
        string $checkIn,
        string $checkOut,
        int $conflictingBookingId,
        array $context
    ): void {
        // المفتاح الطبيعي يعتمد على external_reservation_id إن وُجد. إن لم يوجد، استخدم بصمة مستقرة لتفادي التكرار.
        $naturalKey = $resId ?: sha1(($uid ?? '') . '|' . $checkIn . '|' . $checkOut);

        DB::table('booking_channel_conflicts')->upsert(
            [[
                'apartment_id'            => $apartmentId,
                'channel'                 => 'airbnb',
                'external_reservation_id' => $resId,
                'external_uid'            => $uid,
                'ext_check_in'            => $checkIn,
                'ext_check_out'           => $checkOut,
                // لتفعيل القيد الفريد المقترح: استخدم نفس مجموعة الأعمدة
                'conflicting_booking_id'  => $conflictingBookingId,
                'context'                 => json_encode($context),
                'created_at'              => now(),
                'updated_at'              => now(),
            ]],
            ['apartment_id','channel','external_reservation_id','ext_check_in','ext_check_out'],
            ['conflicting_booking_id','context','updated_at']
        );
    }

    private function cleanupConflicts(int $apartmentId, ?string $resId, ?string $uid): void
    {
        if ($resId) {
            DB::table('booking_channel_conflicts')
                ->where('apartment_id', $apartmentId)
                ->where('channel', 'airbnb')
                ->where('external_reservation_id', $resId)
                ->delete();
            return;
        }
        if ($uid) {
            DB::table('booking_channel_conflicts')
                ->where('apartment_id', $apartmentId)
                ->where('channel', 'airbnb')
                ->whereNull('external_reservation_id')
                ->where('external_uid', $uid)
                ->delete();
        }
    }

    private function deleteOldAirbnbBookings(int $apartmentId, string $icsUrl): int
    {
        // حذف جميع حجوزات Airbnb من نفس المصدر (ICS URL)
        return Booking::query()
            ->where('apartment_id', $apartmentId)
            ->where('is_airbnb_booking', 1)
            ->where('ics_url', $icsUrl)
            ->delete();
    }
}
