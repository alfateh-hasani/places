<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FeatureController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CustomerController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Customer::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/customer');
        $slider = __('cms.customer');
        $sliders = __('cms.customer');

        CRUD::setEntityNameStrings($slider, $sliders);

        if (! backpack_user()->can('customer.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        // if (backpack_user()->can('customer.create')) {
        //     $this->crud->allowAccess('create');
        // }
        // if (backpack_user()->can('customer.update')) {
        //     $this->crud->allowAccess('update');
        // }
        // if (backpack_user()->can('customer.delete')) {
        //     $this->crud->allowAccess('delete');
        // }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {

        CRUD::addColumn([
            'name' => 'first_name',
            'type' => 'text',
            'label' => __('cms.first_name'),
        ]);

        CRUD::addColumn([
            'name' => 'last_name',
            'type' => 'text',
            'label' => __('cms.last_name'),
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'type' => 'email',
            'label' => __('cms.email'),
        ]);

        CRUD::addColumn([
            'name' => 'phone',
            'type' => 'custom_html',
            'label' => __('cms.phone'),
            'value' => fn ($entry) => '<span dir="ltr">'.$entry->phone.'</span>',
            'searchLogic' => fn ($query, $column, $searchTerm) => $query->orWhere('phone', 'like', '%'.$searchTerm.'%'),
        ]);

        CRUD::addColumn([
            'name' => 'reviews_count',
            'type' => 'number',
            'label' => __('cms.reviews_count'),
            'wrapper' => [
                'element' => 'span',
                'class' => 'badge badge-success p-2',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'bookings_count',
            'type' => 'number',
            'label' => __('cms.bookings_count'),
            'wrapper' => [
                'element' => 'span',
                'class' => 'badge badge-primary p-2',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'emergency_phone',
            'type' => 'text',
            'label' => __('cms.emergency_phone'),
        ]);
        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'datetime',
            'label' => __('cms.created_at'),
        ]);

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation() {}

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    // showOperation

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::modifyColumn('phone', [
            'wrapper' => [
                'element' => 'span',
                'style' => 'direction: ltr; display: inline-block;',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'ownerrez_guest_id',
            'type' => 'text',
            'label' => 'OwnerRez Guest ID',
        ]);
    }
}
