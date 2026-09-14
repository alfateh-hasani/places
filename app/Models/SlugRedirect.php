<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Remembers a listing's previous slug so its old public URL can be
 * 301-redirected to the current one instead of 404ing when staff rename it.
 */
class SlugRedirect extends Model
{
    use LogsActivity;

    protected $connection = 'mysql';

    protected $fillable = [
        'redirectable_type',
        'redirectable_id',
        'old_slug',
    ];

    public function redirectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
