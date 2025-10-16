<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayOfWeekPricing extends Model
{
    protected $fillable = ['apartment_id','day_of_week','price'];

    public function apartment() { return $this->belongsTo(Apartment::class); }
}
