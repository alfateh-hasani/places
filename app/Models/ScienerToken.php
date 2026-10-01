<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ScienerToken extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';

    protected $table = 'sciener_tokens';
    protected $fillable = [
        'access_token',
        'refresh_token',
        'uid',
        'openid',
        'scope',
        'token_type',
        'expires_in',
        'expires_at',  
        'username'
    ];

    public $casts = [
        'expires_at'=>'datetime'
    ];
    protected $dates = [
        'expires_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['access_token', 'refresh_token'])
            ->logOnlyDirty();
    }
}
