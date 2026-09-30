<?php

namespace Backpack\Pro\Http\Controllers\Operations;

use Illuminate\Support\Facades\Route;

trait CloneOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param  string  $segment  Name of the current entity (singular). Used as first URL segment.
     * @param  string  $routeName  Prefix of the route name.
     * @param  string  $controller  Name of the current CrudController.
     */
    protected function setupCloneRoutes($segment, $routeName, $controller)
    {
        Route::post($segment.'/{id}/clone', [
            'as'        => $routeName.'.clone',
            'uses'      => $controller.'@clone',
            'operation' => 'clone',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupCloneDefaults()
    {
        $this->crud->allowAccess('clone');

        $this->crud->operation('clone', function () {
            $this->crud->loadDefaultOperationSettingsFromConfig();
        });

        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addButton('line', 'clone', 'view', 'crud::buttons.clone', 'end');
        });
    }

    /**
     * Create a duplicate of the current entry in the datatabase.
     *
     * @param  int  $id
     * @return Response
     */
    public function clone($id)
    {
        $this->crud->hasAccessOrFail('clone');

        $clonedEntry = $this->crud->model->findOrFail($id)->replicate();

        // first insert and get the id of the inserted model.
        $cloned = $clonedEntry->insertGetId($clonedEntry->getAttributes());

        // set the id, and mark the model as existing in the database
        $clonedEntry->{$clonedEntry->getKeyName()} = $cloned;
        $clonedEntry->exists = true;

        // save the cloned model and all it's relationships
        $clonedEntry->push();

        if ($this->crud->getOperationSetting('redirect_after_clone')) {
            $redirectUrl = $this->crud->getOperationSetting('redirect_after_clone') instanceof \Closure
                ? call_user_func($this->crud->getOperationSetting('redirect_after_clone'), $clonedEntry)
                : url($this->crud->route.'/'.$cloned.'/edit');
            return ['redirect' => $redirectUrl];
        }

        return $cloned;
    }
}
