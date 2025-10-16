<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Jobs\SendWelcomeNotification;
class Customer extends Authenticatable implements HasMedia
{
    use HasApiTokens, InteractsWithMedia, Notifiable, CrudTrait, LogsActivity;
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

    //favoriteApartments
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
    

    public function getFullNameAttribute(): string{
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
        });
    }

}
