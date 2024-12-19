<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\FaqRequest;
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
class FaqController extends CrudController
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
        CRUD::setModel(\App\Models\Faq::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/faq');
        $slider = __('cms.faq');
        $sliders = __('cms.faq');

        CRUD::setEntityNameStrings($slider, $sliders);

        
        if (!backpack_user()->can('faq.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('faq.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('faq.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('faq.delete')) {
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
            'name' => 'title_ar',
            'type' => 'text',
            'label' => __('cms.name_ar'),
        ]);
        CRUD::addColumn([
            'name' => 'title_en',
            'type' => 'text',
            'label' =>  __('cms.name_en'),
        ]);

        //description_ar
        CRUD::addColumn([
            'name' => 'description_ar',
            'type' => 'text',
            'label' => __('cms.description_ar'),
        ]);

        CRUD::addColumn([
            'name' => 'description_en',
            'type' => 'text',
            'label' =>  __('cms.description_en'),
        ]);

        CRUD::addColumn([
            'name' => 'sort',
            'type' => 'text',
            'label' =>  __('cms.sort'),
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
        CRUD::setValidation(FaqRequest::class);
        $this->crud->addField([
            'name' => 'title_ar',
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
            'name' => 'title_en',
            'type' => 'text',
            'label' =>  __('cms.name_en'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        $this->crud->addField([
            'name' => 'description_ar',
            'type' => 'ckeditor',
            'label' => __('cms.description_ar'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        $this->crud->addField([
            'name' => 'description_en',
            'type' => 'ckeditor',
            'label' => __('cms.description_en'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        $this->crud->addField([
            'name' => 'faq_category_id',
            'type' => 'select',
            'label' =>  __('cms.faq_category_id'),
            'entity' => 'FaqCategory',
            'attribute' => 'name_ar',
            'model' => 'App\Models\FaqCategory',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
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
