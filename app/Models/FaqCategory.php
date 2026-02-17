<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FaqCategory extends Model
{
    use CrudTrait, LogsActivity;

    protected $connection = 'mysql';
    protected $table = 'faq_categories';
    protected $fillable = ['name_ar', 'name_en', 'sort','slug'];

    public function questions()
    {
        return $this->hasMany(Faq::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
