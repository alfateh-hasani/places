<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PolicyRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PolicyController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PolicyController extends CrudController
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
        CRUD::setModel(\App\Models\Policy::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/policy');
        CRUD::setEntityNameStrings('السياسة', 'السياسات');
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
            'name' => 'description_ar',
            'type' => 'text',
            'label' => 'الوصف بالعربي'
        ]);
        CRUD::addColumn([
            'name' => 'description_en',
            'type' => 'text',
            'label' => 'الوصف بالانجليزي'
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
        CRUD::setValidation(PolicyRequest::class);
        $this->crud->addField([
            'name' => 'description_ar',
            'type' => 'ckeditor',
            'label' => 'الوصف بالعربي',
            'placeholder' => 'الوصف بالعربي',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        $this->crud->addField([
            'name' => 'description_en',
            'type' => 'ckeditor',
            'label' => 'الوصف بالانجليزي',
            'placeholder' => 'الوصف بالانجليزي',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
    }


    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
}
