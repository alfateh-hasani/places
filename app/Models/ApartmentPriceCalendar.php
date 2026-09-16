<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApartmentPriceCalendar extends Model
{
    use LogsActivity;

    protected $connection = 'mysql';

    protected $fillable = ['apartment_id','date','custom_price'];

    public function apartment() { return $this->belongsTo(Apartment::class); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
