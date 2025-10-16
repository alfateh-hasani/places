<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApartmentPriceCalendar extends Model
{
    protected $fillable = ['apartment_id','date','custom_price'];

    public function apartment() { return $this->belongsTo(Apartment::class); }
}
