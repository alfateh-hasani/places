<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LockCrudController;


Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    Route::crud('lock', 'LockCrudController');
    Route::crud('city', 'CityController');
    Route::crud('feature', 'FeatureController');
    Route::crud('apartment', 'ApartmentController');
    Route::crud('building', 'BuildingController');
    Route::crud('policy', 'PolicyController');
    Route::crud('buildings', 'BuildingController');
});



Route::post('admin/lock/{id}/unlock', [LockCrudController::class, 'unlock'])->name('lock.unlock');
Route::post('admin/lock/{id}/add-passcode', [LockCrudController::class, 'addPasscode'])->name('lock.add_passcode');
