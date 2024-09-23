<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lock extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lock_id',
        'passcode',
        'lock_alias',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function getUnlockButton()
    {
        return '<a class="btn btn-xs btn-primary" href="' . url('admin/lock/' . $this->id . '/unlock') . '">Unlock</a>';
    }

    public function getAddPasscodeButton()
    {
        return '<a class="btn btn-xs btn-success" href="' . url('admin/lock/' . $this->id . '/add-passcode') . '">Add Passcode</a>';
    }

}
