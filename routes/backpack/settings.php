<?php

/*
|--------------------------------------------------------------------------
| Backpack\Settings Routes (app override)
|--------------------------------------------------------------------------
|
| Overrides the package routes so the Settings CRUD uses the app's
| SettingCrudController (which adds the OwnerRez master toggle), and adds the
| route that flips OWNERREZ_ENABLED in .env.
|
*/

use App\Http\Controllers\Admin\SettingCrudController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => ['web', backpack_middleware()],
], function () {
    Route::post('ownerrez/toggle', [SettingCrudController::class, 'toggleOwnerRez'])
        ->name('admin.ownerrez.toggle');

    Route::crud(config('backpack.settings.route'), SettingCrudController::class);
});
