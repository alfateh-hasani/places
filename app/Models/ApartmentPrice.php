<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ApartmentPrice extends Model
{
    use LogsActivity;
    protected $fillable = [
        'apartment_id','base_price','weekend_price','long_stay_discount','is_active'
    ];

    public function apartment() { return $this->belongsTo(Apartment::class); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

}