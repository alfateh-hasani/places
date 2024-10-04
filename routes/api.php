<?php

use App\Http\Controllers\Api\{AuthController, CustomerController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('otp/request',  [AuthController::class, 'requestOtp']);
Route::post('otp/verify',  [AuthController::class, 'verifyOtp'])->name('otp.verify');

Route::middleware('auth:api')->group(function () {
    Route::controller(CustomerController::class)->prefix('customer')->group(function () {
        Route::get('my-profile', 'myProfile');
    });

});


