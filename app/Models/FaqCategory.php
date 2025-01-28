<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    use CrudTrait;
    protected $table = 'faq_categories';
    protected $fillable = ['name_ar', 'name_en', 'sort','slug'];

    public function questions()
    {
        return $this->hasMany(Faq::class);
    }
}
