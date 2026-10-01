<?php

namespace App\Http\Controllers\Admin\PermissionManager;

use Backpack\PermissionManager\app\Http\Controllers\UserCrudController as BaseUserCrudController;
use Illuminate\Http\RedirectResponse;

/**
 * Staff users screen gated by the user.* permissions. Only staff who can manage roles
 * (role.update) may assign roles/permissions or edit/delete another role manager —
 * otherwise anyone with user.update could promote themselves to Admin.
 */
class UserCrudController extends BaseUserCrudController
{
    public function setup(): void
    {
        parent::setup();

        if (! backpack_user()->can('user.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        foreach (['create', 'update', 'delete'] as $operation) {
            if (backpack_user()->can("user.{$operation}")) {
                $this->crud->allowAccess($operation);
            }
        }

        $targetId = $this->crud->getCurrentEntryId();

        if ($targetId && ! $this->canManageRoles()) {
            $target = $this->crud->getModel()->newQuery()->find($targetId);

            if ($target?->can('role.update')) {
                $this->crud->denyAccess(['update', 'delete']);
            }
        }
    }

    public function setupCreateOperation(): void
    {
        parent::setupCreateOperation();
        $this->removeRoleFieldsUnlessAllowed();
    }

    public function setupUpdateOperation(): void
    {
        parent::setupUpdateOperation();
        $this->removeRoleFieldsUnlessAllowed();
    }

    public function store(): RedirectResponse
    {
        $this->stripRoleInputUnlessAllowed();

        return parent::store();
    }

    public function update(): RedirectResponse
    {
        $this->stripRoleInputUnlessAllowed();

        return parent::update();
    }

    private function canManageRoles(): bool
    {
        return backpack_user()->can('role.update');
    }

    private function removeRoleFieldsUnlessAllowed(): void
    {
        if (! $this->canManageRoles()) {
            $this->crud->removeField('roles,permissions');
        }
    }

    private function stripRoleInputUnlessAllowed(): void
    {
        if (! $this->canManageRoles()) {
            $this->crud->getRequest()->request->remove('roles');
            $this->crud->getRequest()->request->remove('permissions');
        }
    }
}
