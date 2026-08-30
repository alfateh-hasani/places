<?php

namespace App\Models;

use App\Enums\CustomerSource;
use App\Jobs\SendWelcomeNotification;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Authenticatable implements HasMedia
{
    use CrudTrait, HasApiTokens, InteractsWithMedia, LogsActivity, Notifiable;

    protected $connection = 'mysql';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'emergency_phone',
        'account_verified',
        'job_title',
        'fcm_token',
        'id_number',
        'ownerrez_guest_id',
        'source',
        'blocked_at',
        'block_reason',
        'blocked_by',
    ];

    /**
     * A gateway-payable email: standard structure plus a real TLD of 2+ letters.
     *
     * FILTER_VALIDATE_EMAIL (Laravel's `email:filter`) accepts single-letter TLDs
     * like `test@k.c` and TLD-less domains like `test@d`, but the Geidea payment
     * gateway rejects both with "Invalid email address" (responseCode 110). This
     * pattern is the single source of truth for what the gateway will accept, used
     * both to validate at registration and to sanitize the gateway payload.
     */
    public const GATEWAY_EMAIL_REGEX = '/^[^@\s]+@[^@\s]+\.[A-Za-z]{2,}$/';

    protected function casts(): array
    {
        return [
            'blocked_at' => 'datetime',
            'source' => CustomerSource::class,
        ];
    }

    /**
     * Validation rules for a customer email that the payment gateway will accept.
     *
     * @return array<int, mixed>
     */
    public static function emailValidationRules(?int $ignoreId = null): array
    {
        $unique = Rule::unique('customers', 'email');

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        return [
            'required',
            'email:filter',
            'max:255',
            'regex:'.self::GATEWAY_EMAIL_REGEX,
            $unique,
        ];
    }

    public static function isGatewayValidEmail(?string $email): bool
    {
        return is_string($email)
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && preg_match(self::GATEWAY_EMAIL_REGEX, $email) === 1;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function blockedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function routeNotificationForSms()
    {
        return $this->phone;
    }

    public function routeNotificationForFirebase()
    {
        return $this->fcm_token;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile')->singleFile();
    }

    // favoriteApartments
    public function favoriteApartments()
    {
        return $this->belongsToMany(Apartment::class, 'favorites', 'customer_id', 'apartment_id')->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function getBookingsCountAttribute(): int
    {
        return $this->bookings()->count();
    }

    public function getFullNameAttribute(): string
    {
        return (string) $this->first_name.' '.$this->last_name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    protected static function boot()
    {
        parent::boot();

        // إرسال إشعار الترحيب عند إنشاء حساب جديد
        static::created(function ($customer) {
            SendWelcomeNotification::dispatch($customer);

            // مزامنة Guest مع OwnerRez إذا كانت المزامنة مفعلة
            if (config('ownerrez.sync.sync_guest_data')) {
                \App\Jobs\OwnerRez\SyncCustomerToOwnerRezJob::dispatch($customer->id);
            }
        });

        // مزامنة Guest مع OwnerRez عند تحديث البيانات
        static::updated(function ($customer) {
            if (config('ownerrez.sync.sync_guest_data')) {
                // فقط إذا تم تغيير البيانات المهمة
                if ($customer->isDirty(['first_name', 'last_name', 'email', 'phone'])) {
                    \App\Jobs\OwnerRez\SyncCustomerToOwnerRezJob::dispatch($customer->id);
                }
            }
        });
    }
}
