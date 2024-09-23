<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LockCrudController;
// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('lock', 'LockCrudController');
    Route::crud('city', 'CityCrudController');
}); // this should be the absolute last line of this file


Route::post('admin/lock/{id}/unlock', [LockCrudController::class, 'unlock'])->name('lock.unlock');
Route::post('admin/lock/{id}/add-passcode', [LockCrudController::class, 'addPasscode'])->name('lock.add_passcode');


/**
 * DO NOT ADD ANYTHING HERE.
 */
