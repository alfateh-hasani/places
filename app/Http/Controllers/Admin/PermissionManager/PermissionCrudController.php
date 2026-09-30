<?php

namespace App\Http\Controllers\Admin\PermissionManager;

use Backpack\PermissionManager\app\Http\Controllers\PermissionCrudController as BasePermissionCrudController;

/**
 * Permissions screen. There are no dedicated permission.* abilities, so it is limited to
 * staff who can manage roles; creating/deleting permissions is a developer task.
 */
class PermissionCrudController extends BasePermissionCrudController
{
    public function setup(): void
    {
        parent::setup();

        if (! backpack_user()->can('role.update')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'delete']);
    }
}
