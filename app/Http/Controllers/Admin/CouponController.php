<?php

namespace App\Http\Controllers\Admin;
use App\Http\Requests\CouponRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FeatureController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CouponController extends CrudController
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
        CRUD::setModel(\App\Models\Coupon::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/coupon');
        $slider = __('cms.coupon');
        $sliders = __('cms.coupons');
        CRUD::setEntityNameStrings($slider, $sliders);

        if (!backpack_user()->can('coupon.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('coupon.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('coupon.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('coupon.delete')) {
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
            'name' => 'code',
            'type' => 'text',
            'label' =>  __('cms.code'),
        ]);
        CRUD::addColumn([
            'name' => 'type',
            'type' => 'text',
            'label' =>  __('cms.type'),

        ]);
        CRUD::addColumn([
            'name' => 'discount',
            'type' => 'text',
            'label' =>  __('cms.discount'),
        ]);
        CRUD::addColumn([
            'name' => 'uses_total',
            'type' => 'text',
            'label' =>  __('cms.uses_total'),
        ]);
        CRUD::addColumn([
            'name' => 'uses_customer',
            'type' => 'text',
            'label' =>  __('cms.uses_customer'),
        ]);
        CRUD::addColumn([
            'name' => 'building_id',
            'type' => 'select',
            'label' =>  __('cms.building_id'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => 'App\Models\Building',
        ]);
        CRUD::addColumn([
            'name' => 'apartments',
            'type' => 'relationship',
            'label' =>  __('cms.apartments'),
            'attribute' => 'name_ar',
            'entity' => 'apartments',
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
        CRUD::setValidation(CouponRequest::class);
        CRUD::addField([
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
        CRUD::addField([
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
        //type
        CRUD::addField([
            'name' => 'type',
            'type' => 'select_from_array',
            'label' =>  __('cms.type'),
            'options' => ['fixed' => __('cms.fixed'), 'percentage' =>  __('cms.percentage')],
            'allows_null' => false,
            'default' => 'fixed',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        CRUD::addField([
            'name' => 'code',
            'type' => 'text',
            'label' =>  __('cms.code'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        CRUD::addField([
            'name' => 'discount',
            'type' => 'text',
            'label' =>  __('cms.discount'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        CRUD::addField([
            'name' => 'uses_total',
            'type' => 'text',
            'label' =>  __('cms.uses_total'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        CRUD::addField([
            'name' => 'uses_customer',
            'type' => 'text',
            'label' =>  __('cms.uses_customer'),
            'attributes' => [
                'required' => 'required'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        CRUD::addField([
            'name' => 'building_id',
            'type' => 'select2',
            'label' =>  __('cms.building_id'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => 'App\Models\Building',
        ]);

        CRUD::field('apartments')
            ->type('select2_multiple')
            ->label( __('cms.apartments'))
            ->entity('apartments')
            ->attribute('name_ar')
            ->model('App\Models\Apartment')
            ->pivot('coupon_id', 'apartment_id')
            ->wrapperAttributes([
                'class' => 'form-group col-md-12'
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
