<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ApartmentLabelRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApartmentController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ApartmentLabelController extends CrudController
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
        CRUD::setModel(\App\Models\ApartmentLabel::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/apartment-label');
        $singular = __('cms.apartment_label');
        $plural = __('cms.apartment_labels');
        CRUD::setEntityNameStrings($singular, $plural);
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
            'name' => 'icon',
            'type' => 'image',
            'label' => 'آيقونة',
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
        CRUD::setValidation(ApartmentLabelRequest::class);


        CRUD::addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' =>  __('cms.name_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' =>  __('cms.name_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'description_ar',
            'type' => 'textarea',
            'label' =>  __('cms.description_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'description_en',
            'type' => 'textarea',
            'label' =>  __('cms.description_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::field('icon')
            ->label( __('cms.icon'))
            ->type('upload')
            ->withMedia(['collection' => 'icon'])
            ->wrapperAttributes(['class' => 'form-group col-md-6']);
        ;


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
