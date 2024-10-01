<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ApartmentRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApartmentController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ApartmentController extends CrudController
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
        CRUD::setModel(\App\Models\Apartment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/apartment');
        CRUD::setEntityNameStrings(__('cms.apartment'), __('cms.apartments'));
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
            'label' =>  __('cms.name_ar'),
        ]);
        CRUD::addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
        ]);
        CRUD::addColumn([
            'name' => 'image',
            'type' => 'image',
            'label' =>  __('cms.image'),
            'height' => '50px',
            'width' => '50px',
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
        CRUD::setValidation(ApartmentRequest::class);
        CRUD::field('image')
            ->label(__('cms.image'))
            ->type('upload_multiple')
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
            ]);


        $this->crud->addField([
            'name' => 'is_active',
            'type' => 'select_from_array',
            'label' => __('cms.is_active'),
            'options' => [1 => __('csm.yes'), 0 => __('csm.no')],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' =>  __('cms.name_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);


        $this->crud->addField([
            'name' => 'building_id',
            'type' => 'select2',
            'label' => __('cms.building'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.building_select2'),
        ]);
        $this->crud->addField([
            'name' => 'num_rooms',
            'type' => 'number',
            'label' =>  __('cms.num_rooms'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'num_beds',
            'type' => 'number',
            'label' =>  __('cms.num_beds'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'area',
            'type' => 'number',
            'label' =>  __('cms.area'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'lock',
            'type' => 'select2_multiple',
            'label' => __('cms.lock'),
            'entity' => 'features',
            'attribute' => 'name_ar',
            'model' => \App\Models\Feature::class,
            'pivot' => true,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.lock_select2'),
        ]);
        $this->crud->addField([
            'name' => 'features',
            'type' => 'select2_multiple',
            'label' =>  __('cms.features'),
            'entity' => 'features',
            'attribute' => 'name_ar',
            'model' => \App\Models\Feature::class,
            'pivot' => true,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' =>  __('cms.features_select2'),
        ]);
        $this->crud->addField([
            'name' => 'policy_id',
            'type' => 'select2',
            'label' => __('cms.policy'),
            'entity' => 'policy',
            'attribute' => 'name_ar',
            'model' => \App\Models\Policy::class,
            'pivot' => true,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' =>  __('cms.policy_select2'),
        ]);




        $this->crud->addField([
            'name' => 'description_ar',
            'type' => 'ckeditor',
            'label' =>  __('cms.description_ar'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        $this->crud->addField([
            'name' => 'description_en',
            'type' => 'ckeditor',
            'label' =>  __('cms.description_en'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
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
}
