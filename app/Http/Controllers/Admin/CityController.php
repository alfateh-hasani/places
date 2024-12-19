<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CityRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class CityController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CityController extends CrudController
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
        CRUD::setModel(\App\Models\City::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/city');

        CRUD::setEntityNameStrings('المدينة', 'المدن');

        if (!backpack_user()->can('city.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('city.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('city.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('city.delete')) {
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
            'label' => 'الاسم بالعربي',
        ]);
        CRUD::addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'الاسم بالانجليزي',
        ]);
        //seo_title_ar
        CRUD::addColumn([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
        ]);
        //description_ar
        CRUD::addColumn([
            'name' => 'seo_description_ar',
            'type' => 'text',
            'label' => __('cms.seo_description_ar'),
        ]);

        //slug
        CRUD::addColumn([
            'name' => 'slug',
            'type' => 'text',
            'label' => 'الاسم اللطيف',
        ]);
        CRUD::addColumn([
            'name' => 'image',
            'type' => 'image',
            'label' => 'الصورة',
            'height' => '50px',
            'width' => '50px',
        ]);

        CRUD::addColumn([
            'name' => 'sort_order',
            'type' => 'number',
            'label' => 'الترتيب',
        ]);

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CityRequest::class);

        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' => 'الاسم بالعربي',
            'attributes' => [
                'placeholder' =>  'الاسم بالعربي',
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
                'placeholder' =>  'الاسم بالانجليزي',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_title_ar 
        $this->crud->addField([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
            'attributes' => [
                'placeholder' =>  __('cms.seo_title_ar'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_title_en
        $this->crud->addField([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
            'attributes' => [
                'placeholder' =>  __('cms.seo_title_en'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_description_ar
        $this->crud->addField([
            'name' => 'seo_description_ar',
            'type' => 'textarea',
            'label' => __('cms.seo_description_ar'),
            'attributes' => [
                'placeholder' =>  __('cms.seo_description_ar'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //seo_description_en
        $this->crud->addField([
            'name' => 'seo_description_en',
            'type' => 'textarea',
            'label' =>  __('cms.seo_description_en'),
            'attributes' => [
                'placeholder' =>   __('cms.seo_description_en'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //slug
        $this->crud->addField([
            'name' => 'slug',
            'type' => 'text',
            'label' => 'الاسم اللطيف',
            'attributes' => [
                'placeholder' =>  'Slug',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'sort_order',
            'type' => 'number',
            'label' => 'الترتيب',
            'attributes' => [
                'placeholder' =>  'الترتيب',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::field('image')
            ->label('Main Image')
            ->type('image')
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
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
    }
}
