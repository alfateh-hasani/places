<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdvantageRequest;
use App\Http\Requests\SiteFeatureRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FeatureController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SiteFeatureController extends CrudController
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
        CRUD::setModel(\App\Models\SiteFeature::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/site-feature');
        $slider = __('cms.SiteFeature');
        $sliders = __('cms.SiteFeature');

        CRUD::setEntityNameStrings($slider, $sliders);
        if (!backpack_user()->can('sitefeature.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('sitefeature.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('sitefeature.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('sitefeature.delete')) {
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
            'name' => 'icon',
            'type' => 'image',
            'label' =>  __('cms.icon'),
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
        CRUD::setValidation(SiteFeatureRequest::class);
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
            'name' => 'description_ar',
            'type' => 'textarea',
            'label' =>  __('cms.description_ar'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        $this->crud->addField([
            'name' => 'description_en',
            'type' => 'textarea',
            'label' =>  __('cms.description_en'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::field('icon')
            ->label( __('cms.icon'))
            ->type('upload')
            ->withMedia(['collection' => 'icon']);
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
       CRUD::addColumn([
            'name' => 'description_ar',
            'type' => 'textarea',
            'label' =>  __('cms.description_ar'),
        ]);
        CRUD::addColumn([
            'name' => 'description_en',
            'type' => 'textarea',
            'label' =>  __('cms.description_en'),
        ]);

    }
}
