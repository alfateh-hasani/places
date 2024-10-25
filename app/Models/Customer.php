<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Authenticatable implements HasMedia
{
    use HasApiTokens ,InteractsWithMedia ,Notifiable;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'emergency_phone',
        'account_verified',
        'job_title',
        'fcm_token',
    ];
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function routeNotificationForSms()
    {
        return $this->phone;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile')->singleFile();
    }

    //favoriteApartments
    public function favoriteApartments()
    {
        return $this->belongsToMany(Apartment::class, 'favorites', 'customer_id', 'apartment_id');
    }


}
