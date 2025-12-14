<?php

namespace App\Models;

use App\Jobs\SendWelcomeNotification;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Authenticatable implements HasMedia
{
    use CrudTrait, HasApiTokens, InteractsWithMedia, LogsActivity, Notifiable;

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
    ];

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
