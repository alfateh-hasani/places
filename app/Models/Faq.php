<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use CrudTrait;
    public function faqCategory(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class);
    }
}
