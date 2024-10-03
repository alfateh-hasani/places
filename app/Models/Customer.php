<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Authenticatable implements HasMedia
{
    use HasApiTokens ,InteractsWithMedia;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'emergency_phone',
        'account_verified',
        'job_title',
    ];
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
