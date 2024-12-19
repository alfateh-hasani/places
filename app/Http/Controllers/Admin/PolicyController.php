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

        
        if (!backpack_user()->can('policy.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('policy.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('policy.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('policy.delete')) {
            $this->crud->allowAccess('delete');
        }
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
            'name' => 'name_ar',
            'type' => 'text',
            'label' => 'الاسم بالعربي'
        ]);
        CRUD::addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'الاسم بالانجليزي'
        ]);

        //type
        CRUD::addColumn([
            'name' => 'type',
            'type' => 'array',
            'label' => 'النوع',
            'options' => ['apartment' => 'شقق', 'booking' => 'حجز'],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
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
            'name' => 'name_ar',
            'type' => 'text',
            'label' => 'الاسم بالعربي',
            'placeholder' => 'الاسم بالعربي',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'الاسم بالانجليزي',
            'placeholder' => 'الاسم بالانجليزي',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
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
        $this->crud->addField([
            'name' => 'type',
            'type' => 'select_from_array',
            'label' => 'النوع',
            'options' => ['apartment' => 'شقق', 'booking' => 'حجز'],
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
        CRUD::addColumn([
            'name' => 'description_ar',
            'type' => 'text',
            'label' => 'الوصف بالعربي',
            'limit' => 10000
        ]);
        CRUD::addColumn([
            'name' => 'description_en',
            'type' => 'text',
            'label' => 'الوصف بالانجليزي',
            'limit' => 10000
        ]);
    }
}
