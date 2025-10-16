<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonalPricing extends Model
{
    protected $fillable = ['apartment_id','name','start_date','end_date','multiplier'];


    public function apartment() { return $this->belongsTo(Apartment::class); }
}

