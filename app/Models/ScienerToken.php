<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScienerToken extends Model
{
    use HasFactory;

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
}
