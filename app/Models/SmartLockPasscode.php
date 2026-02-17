<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SmartLockPasscode extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';

    protected $fillable = [
        'smart_lock_id',
        'apartment_id',
        'customer_id',
        'booking_id',
        'nickname',
        'keyboard_pwd',
        'passcode_id',
        'start_date',
        'end_date',
    ];

  
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

     
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

     
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    
    public function smartLock()
    {
        return $this->belongsTo(SmartLock::class, 'smart_lock_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
