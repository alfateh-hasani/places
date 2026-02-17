<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use CrudTrait, HasFactory, LogsActivity;

    protected $connection = 'mysql';
 

    protected $table = 'categories';
 
    protected $guarded = ['id'];
    
    protected $fillable = [
        'name', 'price', 'weekend_price', 'long_stay_discount'
    ];
     
    protected static function booted()
    {
        static::updated(function ($category) {
            $apartmentIds = $category->apartments()->pluck('id');
            
            // تحديث السعر الأساسي إذا تغير
            if ($category->isDirty('price')) {
                // تحديث السعر القديم في جدول apartments (للتوافق)
                $category->apartments()->update(['price' => $category->price]);
            }
            
            // تحديث الأسعار في نظام التسعير الجديد
            if ($category->isDirty(['price', 'weekend_price', 'long_stay_discount'])) {
                foreach ($apartmentIds as $apartmentId) {
                    $updateData = ['is_active' => 1];
                    
                    if ($category->isDirty('price')) {
                        $updateData['base_price'] = $category->price;
                    }
                    
                    if ($category->isDirty('weekend_price')) {
                        $updateData['weekend_price'] = $category->weekend_price;
                    }
                    
                    if ($category->isDirty('long_stay_discount')) {
                        $updateData['long_stay_discount'] = $category->long_stay_discount;
                    }
                    
                    \App\Models\ApartmentPrice::updateOrCreate(
                        ['apartment_id' => $apartmentId],
                        $updateData
                    );
                }
            }
        });
    }

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
