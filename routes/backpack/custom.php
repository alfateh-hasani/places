<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LockController;


Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    Route::crud('smart-lock', 'LockSmartController');
    Route::crud('city', 'CityController');
    Route::crud('feature', 'FeatureController');
    Route::crud('apartment', 'ApartmentController');
    Route::crud('building', 'BuildingController');
    Route::crud('policy', 'PolicyController');
    Route::crud('buildings', 'BuildingController');
    Route::crud('sliders', 'SliderController');
    Route::crud('advantages', 'AdvantageController');
    Route::crud('pages', 'PageController');
});



Route::post('admin/lock/{id}/unlock', [LockController::class, 'unlock'])->name('lock.unlock');
Route::post('admin/lock/{id}/add-passcode', [LockController::class, 'addPasscode'])->name('lock.add_passcode');
