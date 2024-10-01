<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'emergency_phone',
        'account_verified',
        'job_title',
    ];
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
