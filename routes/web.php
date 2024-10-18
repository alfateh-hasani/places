<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\{HomeController,ApartmentController};


Route::group(['prefix' => LaravelLocalization::setLocale()], function()
{   

    // Home Route using HomeController@index
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    Route::get('/apartments', [ApartmentController::class, 'index'])->name('apartments.index');

});

 