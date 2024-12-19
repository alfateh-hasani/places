<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\FaqCategoryRequest;
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
class FaqCategoryController extends CrudController
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
        CRUD::setModel(\App\Models\FaqCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/faq-category');
        $slider = __('cms.category');
        $sliders = __('cms.faq_category');

        CRUD::setEntityNameStrings($slider, $sliders);

        
        if (!backpack_user()->can('faqCategory.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('faqCategory.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('faqCategory.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('faqCategory.delete')) {
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

        //description_ar
        CRUD::addColumn([
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
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
        CRUD::setValidation(FaqCategoryRequest::class);
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

        $this->crud->addField([
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
            'attributes' => [
                'required' => 'required'
            ],
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
