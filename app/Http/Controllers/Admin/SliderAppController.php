<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SliderAppRequest;
use App\Http\Requests\SliderRequest;
use App\Models\Apartment;
use App\Models\City;
use App\Models\Page;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

/**
 * Class FeatureController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SliderAppController extends CrudController
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
        CRUD::setModel(\App\Models\SliderApp::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sliders-app');
        $slider = __('cms.slider_app');
        $sliders = __('cms.sliders_app');

        CRUD::setEntityNameStrings($slider, $sliders);

        
        if (!backpack_user()->can('sliderapp.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('sliderapp.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('sliderapp.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('sliderapp.delete')) {
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

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SliderAppRequest::class);
       
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

        // $this->crud->addField([
        //     'name' => 'related_type',
        //     'type' => 'select2_from_array',
        //     'label' => __('cms.related_type'),
        //     'options' => [
        //         'city' => __('cms.related_city'),
        //         'apartment' => __('cms.related_apartment'),
        //         'page' => __('cms.related_page'),
        //         'general' => __('cms.related_general'),
        //     ],
        //     'attribute' => 'related_type',
        //     'allows_null' => false,
        //     'default' => 'general',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-6'
        //     ]
        // ]);

        // $this->crud->addField([
        //     'name' => 'related_id',
        //     'type' => 'select2_from_ajax',
        //     'label' => __('cms.related_id'),
        //     'attribute' => 'name',
        //     'data_source' => url('admin/get-related-entities'),
        //     'placeholder' => __('cms.select_related_entity'),
        //     'minimum_input_length' => 0,
        //     'dependencies' => ['related_type'],
        //     // 'data' => [
        //     //     'related_type' => 'related_type',
        //     //      'related_id' => 'related_id',
        //     // ],
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-6'
        //     ],
        //     'include_all_form_fields' => true,
        // ]);





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


    public function getRelatedEntities(Request $request)
    {
        $relatedType = $request->input('related_type');
        $entities = match ($relatedType) {
            'city' => City::all(['id', 'name_ar']),
            'apartment' => Apartment::all(['id', 'name_ar']),
            'page' => Page::all(['id', 'name_ar']),
            default => [],
        };
        return response()->json($entities);
    }

}
