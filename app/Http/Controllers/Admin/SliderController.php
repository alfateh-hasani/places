<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SliderRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FeatureController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SliderController extends CrudController
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
        CRUD::setModel(\App\Models\Slider::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sliders');
        $slider = __('cms.slider');
        $sliders = __('cms.sliders');

        CRUD::setEntityNameStrings($slider, $sliders);

        if (!backpack_user()->can('slider.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('slider.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('slider.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('slider.delete')) {
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
            'label' => __('cms.name_ar'),
        ]);
        CRUD::addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' =>  __('cms.name_en'),
        ]);

        CRUD::addColumn([
            'name' => 'image_ar',
            'type' => 'image',
            'label' =>  __('cms.image_ar'),
            'height' => '50px',
            'width' => '50px',
        ]);
        CRUD::addColumn([
            'name' => 'image_en',
            'type' => 'image',
            'label' =>  __('cms.image_en'),
            'height' => '50px',
            'width' => '50px',
        ]);
        CRUD::addColumn([
            'name' => 'image_mobile_ar',
            'type' => 'image',
            'label' =>  __('cms.image_mobile_ar'),
            'height' => '50px',
            'width' => '50px',
        ]);
        CRUD::addColumn([
            'name' => 'image_mobile_en',
            'type' => 'image',
            'label' =>  __('cms.image_mobile_en'),
            'height' => '50px',
            'width' => '50px',
        ]);

        CRUD::addColumn([
            'name' => 'link_ar',
            'type' => 'text',
            'label' => __('cms.link_ar'),
        ]);
    
        CRUD::addColumn([
            'name' => 'link_en',
            'type' => 'text',
            'label' => __('cms.link_en'),
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
        CRUD::setValidation(SliderRequest::class);
        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' =>  __('cms.name_ar'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' =>  __('cms.name_en'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);



        CRUD::field('image_ar')
            ->label(__('cms.image_ar'))
            ->type('image')
            ->withMedia(['collection' => 'image_ar'])
            ->wrapperAttributes([
                'class' => 'form-group col-md-6'
            ]);


        CRUD::field('image_en')
            ->label(__('cms.image_en'))
            ->type('image')
            ->withMedia(['collection' => 'image_en'])
            ->wrapperAttributes([
                'class' => 'form-group col-md-6'
            ]);
        CRUD::field('image_mobile_ar')
            ->label(__('cms.image_mobile_ar'))
            ->type('image')
            ->withMedia(['collection' => 'image_mobile_ar'])
            ->wrapperAttributes([
                'class' => 'form-group col-md-6'
            ]);
        CRUD::field('image_mobile_en')
            ->label(__('cms.image_mobile_en'))
            ->type('image')
            ->withMedia(['collection' => 'image_mobile_en'])
            ->wrapperAttributes([
                'class' => 'form-group col-md-6'
            ]);

        
        $this->crud->addField([
            'name' => 'sort_order',
            'type' => 'number',
            'label' => __('cms.sort_order'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);
    
        $this->crud->addField([
            'name' => 'link_ar',
            'type' => 'text',
            'label' => __('cms.link_ar'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);
    
        $this->crud->addField([
            'name' => 'link_en',
            'type' => 'text',
            'label' => __('cms.link_en'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
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

    //showOperation

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
}
