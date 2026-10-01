<?php

namespace App\Http\Controllers\Admin\PermissionManager;

use Backpack\PermissionManager\app\Http\Controllers\RoleCrudController as BaseRoleCrudController;

/**
 * Roles screen gated by the role.* permissions.
 */
class RoleCrudController extends BaseRoleCrudController
{
    public function setup(): void
    {
        parent::setup();

        if (! backpack_user()->can('role.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        foreach (['create', 'update', 'delete'] as $operation) {
            if (backpack_user()->can("role.{$operation}")) {
                $this->crud->allowAccess($operation);
            }
        }
    }
}
