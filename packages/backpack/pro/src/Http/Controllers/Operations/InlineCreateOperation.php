<?php

namespace Backpack\Pro\Http\Controllers\Operations;

use Backpack\CRUD\app\Exceptions\AccessDeniedException;
use Illuminate\Support\Facades\Route;
use Prologue\Alerts\Facades\Alert;

trait InlineCreateOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param  string  $segment  Name of the current entity (singular). Used as first URL segment.
     * @param  string  $routeName  Prefix of the route name.
     * @param  string  $controller  Name of the current CrudController.
     */
    protected function setupInlineCreateRoutes($segment, $routeName, $controller)
    {
        Route::post($segment.'/inline/create/modal', [
            'as'        => $segment.'-inline-create',
            'uses'      => $controller.'@getInlineCreateModal',
            'operation' => 'InlineCreate',
        ]);
        Route::post($segment.'/inline/create', [
            'as'        => $segment.'-inline-create-save',
            'uses'      => $controller.'@storeInlineCreate',
            'operation' => 'InlineCreate',
        ]);
    }

    /**
     * Setup the operation default settings. In this operation we want to make sure that the defaults are only applied when
     * the Operation is needed because it relies on calling other operation methods.
     */
    protected function setupInlineCreateDefaults()
    {
        $this->crud->allowAccess('inline-create');

        if ($this->crud->getCurrentOperation() !== 'InlineCreate') {
            return;
        }

        if (method_exists($this, 'setup')) {
            $this->setup();
        }

        $this->crud->applyConfigurationFromSettings('create');

        if (method_exists($this, 'setupCreateOperation')) {
            $this->setupCreateOperation();
        }
    }

    /**
     * Returns the HTML of the create form. It's used by the CreateInline operation, to show that form
     * inside a popup (aka modal).
     */
    public function getInlineCreateModal()
    {
        $this->inlineCreateCheckOperationAccess();

        if (! request()->has('entity')) {
            abort(500, 'No "entity" inside the request.', ['developer-error-exception']);
        }

        return view(
            $this->crud->getFirstFieldView('relationship.inc.inline_create_modal'),
            [
                'fields'             => $this->crud->getCreateFields(),
                'action'             => 'create',
                'crud'               => $this->crud,
                'entity'             => request()->get('entity'),
                'modalClass'         => request()->get('modal_class'),
                'parentLoadedAssets' => request()->get('parent_loaded_assets'),
            ]
        );
    }

    /**
     * Runs the store() function in controller like a regular crud create form.
     * Developer might overwrite this if they want some custom save behaviour when added on the fly.
     *
     * @return void
     */
    public function storeInlineCreate()
    {
        $this->inlineCreateCheckOperationAccess();

        $result = $this->store();

        // do not carry over the flash messages from the Create operation
        Alert::flush();

        return $result;
    }

    private function inlineCreateCheckOperationAccess()
    {
        if (! $this->crud->hasAccessToAll(['create', 'inline-create'])) {
            throw new AccessDeniedException(trans('backpack::crud.unauthorized_access', ['access' => 'create/inline-create']), 403);
        }
    }
}
