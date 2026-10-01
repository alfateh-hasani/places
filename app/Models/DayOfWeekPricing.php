<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DayOfWeekPricing extends Model
{
    use LogsActivity;

    protected $connection = 'mysql';

    protected $fillable = ['apartment_id','day_of_week','price'];

    public function apartment() { return $this->belongsTo(Apartment::class); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
