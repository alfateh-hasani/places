<?php

namespace App\Models;

use App\Events\BookingApproved;
use App\Jobs\SendBookingConfirmedNotification;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use CrudTrait, LogsActivity;

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
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
        ];
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

        // إرسال إشعار تأكيد الحجز عند تغيير الحالة إلى approved
        static::updated(function ($booking) {
            if ($booking->isDirty('status') && $booking->status === 'approved') {
                SendBookingConfirmedNotification::dispatch($booking);

                // إطلاق event للمزامنة مع OwnerRez
                event(new BookingApproved($booking));
            }
        });

        // إطلاق event للمزامنة مع OwnerRez عند إنشاء حجز بحالة approved مباشرة
        static::created(function ($booking) {
            if ($booking->status === 'approved' && $booking->payment_status === 'paid') {
                event(new BookingApproved($booking));
            }
        });
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
        $statuses = [
            'pending' => __('cms.status_pending'),
            'approved' => __('cms.status_approved'),
            'canceled' => __('cms.status_canceled'),
            'customer_canceled' => __('cms.status_customer_canceled'),
            'rejected' => __('cms.status_rejected'),
            'finished' => __('cms.status_finished'),
            'booked' => __('cms.status_booked'),
        ];

        $button = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
        return $this->status === 'approved' &&
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
            ->logAll();
    }

    /**
     * التحقق من إمكانية إلغاء الحجز بناءً على سياسة الإلغاء
     */
    public function canBeCanceled(): bool
    {
        // التحقق من أن الحجز في حالة approved و paid
        if ($this->status !== 'approved' || $this->payment_status !== 'paid') {
            return false;
        }

        // الحصول على سياسة الإلغاء من جدول settings
        $setting = \DB::table('settings')->where('key', 'cancel_before_hours')->first();
        $cancelBeforeHours = $setting ? (int) $setting->value : 24;

        // حساب الفرق بالساعات بين الآن ووقت تسجيل الدخول
        $checkInDateTime = $this->check_in->setTimeFromTimeString($this->check_in_time->format('H:i:s'));
        $hoursUntilCheckIn = now()->diffInHours($checkInDateTime, false);

        // التحقق من أن الوقت المتبقي أكبر من أو يساوي المطلوب
        return $hoursUntilCheckIn >= $cancelBeforeHours;
    }
}
