<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    protected $table = 'faq_categories';
    protected $fillable = ['name_ar', 'name_en', 'sort'];

    public function questions()
    {
        return $this->hasMany(Faq::class);
    }
}
