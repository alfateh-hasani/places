<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Faq extends Model
{
    use CrudTrait, HasTranslations, LogsActivity;

    protected $connection = 'mysql';

    public function faqCategory(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
