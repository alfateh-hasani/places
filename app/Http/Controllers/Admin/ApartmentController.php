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
    use \Backpack\Pro\Http\Controllers\Operations\DropzoneOperation;
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
        if (!backpack_user()->can('apartment.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('apartment.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('apartment.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('apartment.delete')) {
            $this->crud->allowAccess('delete');
        }
        if (backpack_user()->hasRole('supervisor')) {
            $this->crud->query->whereHas('building', function ($query) {
                $query->where('supervisor_id', backpack_user()->id);
            });
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
            'label' =>  __('cms.name_ar'),
        ]);
        CRUD::addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
        ]);
        CRUD::addColumn([
            'name' => 'building_id',
            'type' => 'select',
            'label' => __('cms.building'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
        ]);
        CRUD::addColumn([
            'name' => 'num_rooms',
            'type' => 'number',
            'label' =>  __('cms.num_rooms'),
        ]);
        CRUD::addColumn([
            'name' => 'num_beds',
            'type' => 'number',
            'label' =>  __('cms.num_beds'),
        ]);
        CRUD::addColumn([
            'name' => 'area',
            'type' => 'number',
            'label' =>  __('cms.area'),
        ]);
        //bathrooms_count
        CRUD::addColumn([
            'name' => 'bathrooms_count',
            'type' => 'number',
            'label' =>  __('cms.bathrooms_count'),
        ]);
        CRUD::addColumn([
            'name' => 'price',
            'type' => 'number',
            'label' =>  __('cms.price'),
        ]);
        CRUD::addColumn([
            'name' => 'is_active',
            'type' => 'boolean',
            'label' =>  __('cms.is_active'),
        ]);
        CRUD::addColumn([
            'name' => 'smart_lock_id',
            'type' => 'select',
            'label' => __('cms.lock'),
            'entity' => 'lock',
            'attribute' => 'full_name',
            'model' => \App\Models\SmartLock::class,
        ]);
        CRUD::addColumn([
            'name' => 'policy_id',
            'type' => 'select',
            'label' => __('cms.policy'),
            'entity' => 'policy',
            'attribute' => 'name_ar',
            'model' => \App\Models\Policy::class,
        ]);
        CRUD::addColumn([
            'name' => 'image_view',
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
            ->type('dropzone')
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
            ])->wrapperAttributes([
                'class' => 'form-group col-md-12',
            ]);
         


        $this->crud->addField([
            'name' => 'is_active',
            'type' => 'select_from_array',
            'label' => __('cms.is_active'),
            'options' => [1 => __('cms.yes'), 0 => __('cms.no')],
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
        //bathrooms_count
        $this->crud->addField([
            'name' => 'bathrooms_count',
            'type' => 'number',
            'label' =>  __('cms.bathrooms_count'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //adults_count
        $this->crud->addField([
            'name' => 'adults_count',
            'type' => 'number',
            'label' =>  __('cms.adults_count'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //children_count
        $this->crud->addField([
            'name' => 'children_count',
            'type' => 'number',
            'label' =>  __('cms.children_count'),
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
            'name' => 'floor_number',
            'type' => 'number',
            'label' =>  __('cms.floor_number'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'unit_number',
            'type' => 'number',
            'label' =>  __('cms.unit_number'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);


        $this->crud->addField([
            'name' => 'smart_lock_id',
            'type' => 'select2',
            'label' => __('cms.lock'),
            'entity' => 'lock',
            'attribute' => 'full_name',
            'model' => \App\Models\SmartLock::class,
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
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' =>  __('cms.policy_select2'),
        ]);

        $this->crud->addField([
            'name' => 'price',
            'type' => 'number',
            'label' =>  __('cms.price'),
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
            'label' =>  __('cms.slug'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
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
        //seo_title_ar
        $this->crud->addField([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' =>  __('cms.seo_title_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_title_en
        $this->crud->addField([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' =>  __('cms.seo_title_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_description_ar
        $this->crud->addField([
            'name' => 'seo_description_ar',
            'type' => 'text',
            'label' =>  __('cms.seo_description_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_description_en
        $this->crud->addField([
            'name' => 'seo_description_en',
            'type' => 'text',
            'label' =>  __('cms.seo_description_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        //Label
        $this->crud->addField([
            'name' => 'labels',
            'type' => 'select2_multiple',
            'label' =>  __('cms.apartment_label'),
            'entity' => 'labels',
            'attribute' => 'name_ar',
            'model' => \App\Models\ApartmentLabel::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' =>  __('cms.label_select2'),
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
        CRUD::addColumn([
            'name' => 'description_ar',
            'type' => 'text',
            'label' =>  __('cms.description_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'limit' => 10000000,
        ]);
        CRUD::addColumn([
            'name' => 'description_en',
            'type' => 'text',
            'label' =>  __('cms.description_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'limit' => 10000000,
        ]);

    }

}
