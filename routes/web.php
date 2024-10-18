<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\HomeController;


Route::group(['prefix' => LaravelLocalization::setLocale()], function()
{

    // Home Route using HomeController@index
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
});

 