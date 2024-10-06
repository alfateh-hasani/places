<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\LockRequest;
use App\Http\Requests\SmartLockRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
class LockSmartController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SmartLock::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/smart-lock');
        $singular = __('cms.smart_lock');
        $plural = __('cms.smart_locks');
        CRUD::setEntityNameStrings($singular, $plural);
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'lock_id',
            'label' =>  __('cms.lock_id'),
        ]);
        CRUD::addColumn([
            'name' => 'lock_name',
            'label' => __('cms.lock_name'),
        ]);
        CRUD::addColumn([
            'name' => 'lock_alias',
            'label' =>  __('cms.lock_alias'),
        ]);

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SmartLockRequest::class);
        $this->crud->addField([
            'name' => 'lock_id',
            'label' => __('cms.lock_id'),
            'type' => 'text',
        ]);
        $this->crud->addField([
            'name' => 'lock_name',
            'label' =>  __('cms.lock_name'),
            'type' => 'text',
        ]);
        $this->crud->addField([
            'name' => 'lock_alias',
            'label' =>  __('cms.lock_alias'),
            'type' => 'text',
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

     protected function setupShowOperation(){
        $this->setupListOperation();

     }




}
