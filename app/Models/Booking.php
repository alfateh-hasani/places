<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CancelSource;
use App\Enums\DateChangeStatus;
use App\Events\BookingApproved;
use App\Events\BookingCancelled;
use App\Jobs\SendBookingConfirmedNotification;
use App\Jobs\SendNewBookingStaffNotification;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use CrudTrait, LogsActivity;

    protected $connection = 'mysql';

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'discount' => 'float',
            'passcode_generated_at' => 'datetime',
            'refund_date' => 'datetime',
            'refund_amount' => 'float',
            'last_refund_attempt_at' => 'datetime',
        ];
    }

    public function refunds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Refund::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {

            $booking->uuid = (string) Str::uuid();

            if (! $booking->is_airbnb_booking) {
                do {
                    $randomNumber = mt_rand(0, 999999);
                    $bookingNumber = '00'.str_pad($randomNumber, 6, '0', STR_PAD_LEFT);
                } while (Booking::where('number_of_booking', $bookingNumber)->exists());
                $booking->number_of_booking = $bookingNumber;
            }
        });

        // تسجيل مصدر الإلغاء تلقائياً: أي انتقال إلى "طلب إلغاء" بلا مصدر محدَّد يُعتبر
        // من العميل (مسارات التطبيق/الموقع). المسار الإداري يعيّن 'staff' صراحةً قبل الحفظ،
        // فلا يُستبدل. هكذا لا نحتاج تعديل أي متحكم API لضبط المصدر.
        static::updating(function ($booking) {
            if ($booking->isDirty('status')
                && $booking->status === BookingStatus::CancellationRequested->value
                && empty($booking->cancel_source)) {
                $booking->cancel_source = CancelSource::Customer->value;
            }
        });

        // إرسال إشعار تأكيد الحجز عند تغيير الحالة إلى approved
        static::updated(function ($booking) {
            if ($booking->isDirty('status') && $booking->status === BookingStatus::Approved->value) {
                SendBookingConfirmedNotification::dispatch($booking);

                // Notify staff — the booking is now confirmed (payment complete),
                // not on the earlier pending/pre-payment step.
                self::notifyStaffOfConfirmedBooking($booking);

                // إطلاق event للمزامنة مع OwnerRez ولتوفير كود الدخول
                event(new BookingApproved($booking));
            }

            // إطلاق event لإلغاء كود الدخول عند إلغاء الحجز (من العميل أو الإدارة أو OwnerRez)
            if ($booking->isDirty('status') && in_array($booking->status, [BookingStatus::Canceled->value, BookingStatus::CancellationRequested->value], true)) {
                event(new BookingCancelled($booking, $booking->getOriginal('status')));
            }
        });

        // إطلاق event للمزامنة مع OwnerRez عند إنشاء حجز بحالة approved مباشرة
        static::created(function ($booking) {
            if ($booking->status === BookingStatus::Approved->value && $booking->payment_status === 'paid') {
                // A booking created already-paid (e.g. direct dashboard booking) is
                // confirmed immediately — notify staff here, not on a pending step.
                self::notifyStaffOfConfirmedBooking($booking);

                event(new BookingApproved($booking));
            }
        });
    }

    /**
     * Notify staff of a newly confirmed (paid) customer/direct booking. Imported
     * OwnerRez/Airbnb reservations are skipped to avoid noise from bulk syncs.
     */
    private static function notifyStaffOfConfirmedBooking(self $booking): void
    {
        $isImported = $booking->is_airbnb_booking || $booking->booking_source === 'ownerrez';

        if (! $isImported) {
            SendNewBookingStaffNotification::dispatch($booking);
        }
    }

    // coupon

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    // price_per_night - يتم حفظه مباشرة في قاعدة البيانات
    public function getPricePerNightAttribute()
    {
        return $this->one_night_price ?? 0;
    }

    public function getChangeStatusButton()
    {
        // "طلب إلغاء" (CancellationRequested) عمداً غير مُتاح كخيار يدوي: الإلغاء يبدأ
        // عبر إجراء الإلغاء الموجّه (زر "إدارة الإلغاء") فقط، حتى لا يستطيع الموظف
        // تعيين حالة "طلب إلغاء العميل" يدوياً ثم رفض طلبه بنفسه.
        $statuses = [];
        foreach (BookingStatus::cases() as $case) {
            if ($case === BookingStatus::CancellationRequested) {
                continue;
            }
            $statuses[$case->value] = $case->label();
        }

        $button = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
                            '.__('cms.change_status').'
                        </button>
                        <div class="dropdown-menu">';

        foreach ($statuses as $status => $label) {
            $url = url("admin/booking/{$this->id}/change-status/{$status}");
            $button .= '<form method="POST" action="'.$url.'" style="display:inline;">
                            '.csrf_field().'
                            <button class="dropdown-item" type="submit">'.$label.'</button>
                        </form>';
        }

        $button .= '</div></div>';

        return $button;
    }

    public function getChangePaymentStatusButton()
    {
        $paymentStatuses = [
            'pending' => __('cms.payment_status_pending'),
            'paid' => __('cms.payment_status_paid'),
            'failed' => __('cms.payment_status_failed'),
        ];

        $button = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-warning dropdown-toggle" 
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            '.__('cms.change_payment_status').'
                        </button>
                        <div class="dropdown-menu">';

        foreach ($paymentStatuses as $status => $label) {
            $url = url("admin/booking/{$this->id}/change-payment-status/{$status}");
            $button .= '<form method="POST" action="'.$url.'" style="display:inline;">
                            '.csrf_field().'
                            <button class="dropdown-item" type="submit">'.$label.'</button>
                        </form>';
        }

        $button .= '</div></div>';

        return $button;
    }

    // buildings
    public function building()
    {
        return $this->hasOneThrough(
            Building::class,
            Apartment::class,
            'id',
            'id',
            'apartment_id',
            'building_id'
        );
    }

    // Smart Lock Passcodes
    public function smartLockPasscodes()
    {
        return $this->hasMany(SmartLockPasscode::class);
    }

    // Date-change requests
    public function dateChangeRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DateChangeRequest::class);
    }

    public function hasOpenDateChangeRequest(): bool
    {
        return $this->dateChangeRequests()
            ->whereIn('status', DateChangeStatus::openValues())
            ->exists();
    }

    // Get active passcode for this booking
    public function getActivePasscode()
    {
        return $this->smartLockPasscodes()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest()
            ->first();
    }

    // Passcode status methods
    public function markPasscodeAsPending()
    {
        $this->update([
            'passcode_status' => 'pending',
            'passcode_generated_at' => null,
            'passcode_error' => null,
        ]);
    }

    public function markPasscodeAsGenerated()
    {
        $this->update([
            'passcode_status' => 'generated',
            'passcode_generated_at' => now(),
            'passcode_error' => null,
        ]);
    }

    public function markPasscodeAsFailed($error = null)
    {
        $this->update([
            'passcode_status' => 'failed',
            'passcode_error' => $error,
            'passcode_retry_count' => $this->passcode_retry_count + 1,
        ]);
    }

    public function markPasscodeAsRetryScheduled()
    {
        $this->update([
            'passcode_status' => 'retry_scheduled',
        ]);
    }

    // Check if passcode needs to be generated
    public function needsPasscodeGeneration()
    {
        return $this->status === BookingStatus::Approved->value &&
               $this->passcode_status !== 'generated' &&
               $this->smartLockPasscodes()->count() === 0;
    }

    // Get retry attempt for this booking
    public function retryAttempt()
    {
        return $this->hasOne(PasscodeRetryAttempt::class);
    }

    public function getTotalPriceBeforeTaxAttribute()
    {
        return $this->total_price - $this->tax;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /** The booking's status as a typed enum (null if the stored value is unknown). */
    public function statusEnum(): ?BookingStatus
    {
        return BookingStatus::tryFrom((string) $this->status);
    }

    /** A cancellation has been requested (by customer or staff) and is under review. */
    public function isCancellationRequested(): bool
    {
        return $this->status === BookingStatus::CancellationRequested->value;
    }

    /** The cancellation is finalized and the unit is freed locally. */
    public function isCanceled(): bool
    {
        return $this->status === BookingStatus::Canceled->value;
    }

    /** This booking exists in OwnerRez, so it can only be cancelled there (via the UI). */
    public function isLinkedToOwnerRez(): bool
    {
        return ! empty($this->ownerrez_booking_id);
    }

    /** Whether a staff member (not the customer) started the cancellation. */
    public function cancellationStartedByStaff(): bool
    {
        return $this->cancel_source === CancelSource::Staff->value;
    }

    /** The cancellation source as a typed enum, if recorded. */
    public function cancelSourceEnum(): ?CancelSource
    {
        return $this->cancel_source ? CancelSource::tryFrom((string) $this->cancel_source) : null;
    }

    public function canBeCanceled(): bool
    {
        // التحقق من أن الحجز في حالة approved و paid
        if ($this->status !== BookingStatus::Approved->value || $this->payment_status !== 'paid') {
            return false;
        }

        // لا يمكن إلغاء حجز له طلب تعديل تواريخ مفتوح (بانتظار دفع/مراجعة/تطبيق) —
        // يجب حل الطلب (رفضه/سحبه) أولاً حتى لا يبقى طلب "يتيم" على حجز أُلغي.
        if ($this->hasOpenDateChangeRequest()) {
            return false;
        }

        // الحصول على سياسة الإلغاء من جدول settings
        $setting = \DB::table('settings')->where('key', 'cancel_before_hours')->first();
        $cancelBeforeHours = $setting ? (int) $setting->value : 24;

        $checkInTime = $this->check_in_time?->format('H:i:s');
        if (! $checkInTime) {
            $checkInTime = '16:00:00';
        }
        // حساب الفرق بالساعات بين الآن ووقت تسجيل الدخول
        $checkInDateTime = $this->check_in?->setTimeFromTimeString($checkInTime);
        $hoursUntilCheckIn = now()->diffInHours($checkInDateTime, false);

        // التحقق من أن الوقت المتبقي أكبر من أو يساوي المطلوب
        return $hoursUntilCheckIn >= $cancelBeforeHours;
    }
}
