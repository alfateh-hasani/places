<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ApartmentRequest;
use App\Http\Requests\PageRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApartmentController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PageController extends CrudController
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
        CRUD::setModel(\App\Models\Page::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pages');
        CRUD::setEntityNameStrings(__('cms.page'), __('cms.pages'));
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
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
        ]);
        CRUD::addColumn([
            'name' => 'template',
            'type' => 'text',
            'label' => __('cms.template'),
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
        CRUD::setValidation(PageRequest::class);
        CRUD::field('image')
            ->label(__('cms.image'))
            ->type('image')
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
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
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'template',
            'type' => 'select_from_array',
            'label' => __('cms.template'),
            'options' => [
                'default' => 'Default',
                'contact' => 'Contact',
                'about' => 'About',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_description_ar',
            'type' => 'textarea',
            'label' => __('cms.seo_description_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_description_en',
            'type' => 'textarea',
            'label' => __('cms.seo_description_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'content_ar',
            'type' => 'ckeditor',
            'label' =>  __('cms.content_ar'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        $this->crud->addField([
            'name' => 'content_en',
            'type' => 'ckeditor',
            'label' =>  __('cms.content_en'),
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

    //show operation
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        $this->crud->addColumn([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
        ]);
        $this->crud->addColumn([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
        ]);
        $this->crud->addColumn([
            'name' => 'seo_description_ar',
            'type' => 'text',
            'label' => __('cms.seo_description_ar'),
        ]);
        $this->crud->addColumn([
            'name' => 'seo_description_en',
            'type' => 'text',
            'label' => __('cms.seo_description_en'),
        ]);
        $this->crud->addColumn([
            'name' => 'content_ar',
            'type' => 'text',
            'label' => __('cms.content_ar'),
        ]);
        $this->crud->addColumn([
            'name' => 'content_en',
            'type' => 'text',
            'label' => __('cms.content_en'),
        ]);



    }
}
