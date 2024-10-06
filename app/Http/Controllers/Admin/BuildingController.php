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
            'label' =>'الاسم بالعربي',
            'attributes' => [
                'placeholder' => 'الاسم بالعربي',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'الاسم بالانجليزي',
            'attributes' => [
                'placeholder' => 'الاسم بالانجليزي',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'city_id',
            'type' => 'select2',
            'label' => 'المدينة',
            'entity' => 'city',
            'attribute' => 'name_ar',
            'model' => \App\Models\City::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => 'اختر المدينة',
        ]);
        $this->crud->addField([
            'name' => 'address',
            'type' => 'text',
            'label' => 'العنوان',
            'attributes' => [
                'placeholder' => 'العنوان',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'latitude',
            'type' => 'text',
            'label' =>  __('cms.latitude'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'longitude',
            'type' => 'text',
            'label' =>  __('cms.longitude'),
            'attributes' => [
                'required' => 'required',
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
            'name' => 'address',
            'type' => 'textarea',
            'label' => 'العنوان',
            'max' => 19100,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        CRUD::addColumn([
            'name' => 'latitude',
            'type' => 'text',
            'label' =>  __('cms.latitude'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addColumn([
            'name' => 'longitude',
            'type' => 'text',
            'label' =>  __('cms.longitude'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
    }
}
