<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use CrudTrait,HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function buildings()
    {
        return $this->hasMany(Building::class, 'supervisor_id');
    }

    public function bookings()
    {
        return $this->hasManyThrough(
            Booking::class,
            Apartment::class, 
            'building_id',     
            'apartment_id',    
            'id',            
            'id'              
        )->whereHas('apartment.building', function ($query) {
            $query->where('supervisor_id', $this->id);
        });
    }


}
