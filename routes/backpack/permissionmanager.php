<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backpack\PermissionManager Routes
|--------------------------------------------------------------------------
|
| Overrides the package routes (the package loads this file instead of its own when it
| exists) so staff/role management goes through controllers that enforce permissions.
|
*/

Route::group([
    'namespace' => 'App\Http\Controllers\Admin\PermissionManager',
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => ['web', backpack_middleware()],
], function () {
    Route::crud('permission', 'PermissionCrudController');
    Route::crud('role', 'RoleCrudController');
    Route::crud('user', 'UserCrudController');
});
