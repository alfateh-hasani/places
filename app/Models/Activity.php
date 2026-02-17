<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use CrudTrait;

    public function causer(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo()->useConnection(config('database.default'));
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        $morphTo = $this->morphTo();

        if (config('activitylog.subject_returns_soft_deleted_models')) {
            $morphTo->withTrashed();
        }

        return $morphTo->useConnection(config('database.default'));
    }
}
