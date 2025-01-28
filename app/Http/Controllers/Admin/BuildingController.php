<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BuildingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class BuildingController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BuildingController extends CrudController
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
        CRUD::setModel(\App\Models\Building::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/building');
        CRUD::setEntityNameStrings('مبنى', 'البناء');

        if (!backpack_user()->can('building.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('building.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('building.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('building.delete')) {
            $this->crud->allowAccess('delete');
        }
        if (backpack_user()->hasRole('supervisor')) {
            $this->crud->addClause('where', 'supervisor_id', backpack_user()->id);
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


        $this->crud->addColumn([
            'name' => 'name_ar',
            'type' => 'text',
            'label' => 'الاسم بالعربي',
        ]);
        $this->crud->addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'الاسم بالانجليزي',
        ]);
        $this->crud->addColumn([
            'name' => 'image',
            'type' => 'image',
            'label' => 'الصورة',
        ]);
        $this->crud->addColumn([
            'name' => 'city_id',
            'type' => 'select',
            'label' => 'المدينة',
            'entity' => 'city',
            'attribute' => 'name_ar',
            'model' => \App\Models\City::class,
        ]);

        //supervisor_id
        $this->crud->addColumn([
            'name' => 'supervisor_id',
            'type' => 'select',
            'label' => 'المشرف',
            'entity' => 'supervisor',
            'attribute' => 'name',
            'model' => \App\Models\User::class,
        ]);
        //check_out_time check_in_time
        $this->crud->addColumn([
            'name' => 'check_in_time',
            'type' => 'time',
            'label' =>  __('cms.check_in_time'),
        ]);

        $this->crud->addColumn([
            'name' => 'check_out_time',
            'type' => 'time',
            'label' =>  __('cms.check_out_time'),
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
        CRUD::setValidation(BuildingRequest::class);
        //add image
        CRUD::field('image')
            ->label('الصورة')
            ->type('image')
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
            ]);

        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' => __('cms.name_ar'),
            'attributes' => [
                'placeholder' =>  __('cms.name_ar'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' =>  __('cms.name_en'),
            'attributes' => [
                'placeholder' =>  __('cms.name_en'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'address_ar',
            'type' => 'text',
            'label' => __('cms.address_ar'),
            'attributes' => [
                'placeholder' => __('cms.address_ar'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'address_en',
            'type' => 'text',
            'label' => __('cms.address_en'),
            'attributes' => [
                'placeholder' =>  __('cms.address_en'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'city_id',
            'type' => 'select2',
            'label' =>  __('cms.city'),
            'entity' => 'city',
            'attribute' => 'name_ar',
            'model' => \App\Models\City::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' =>  __('cms.city_select2'),
        ]);

        $this->crud->addField([
            'name' => 'supervisor_id',
            'type' => 'select2',
            'label' =>  __('cms.supervisor'),
            'entity' => 'supervisor',
            'attribute' => 'name',
            'model' => \App\Models\User::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' =>  __('cms.supervisor_select2'),
        ]);

        //map_link
        $this->crud->addField([
            'name' => 'latitude',
            'type' => 'text',
            'label' => __('cms.latitude'),
            
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        
        $this->crud->addField([
            'name' => 'longitude',
            'type' => 'text',
            'label' => __('cms.longitude'),
             
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        //add check_in_time check_out_time
        $this->crud->addField([
            'name' => 'check_in_time',
            'type' => 'time',
            'label' =>  __('cms.check_in_time'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'check_out_time',
            'type' => 'time',
            'label' =>  __('cms.check_out_time'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);


        $this->crud->addField([
            'name' => 'map',
            'type' => 'textarea',
            'label' =>  __('cms.map'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'slug',
            'type' => 'text',
            'label' => 'الاسم اللطيف',
            'attributes' => [
                'placeholder' => 'slug',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
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

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::addColumn([
            'name' => 'address_ar',
            'type' => 'textarea',
            'label' => __('cms.address_ar'),
            'max' => 19100,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        CRUD::addColumn([
            'name' => 'address_en',
            'type' => 'textarea',
            'label' =>  __('cms.address_en'),
            'max' => 19100,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        //link
        CRUD::addColumn([
            'name' => 'link',
            'type' => 'text',
            'label' =>  __('cms.map_link'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

    }
}
