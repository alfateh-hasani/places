<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ContactUsRequest;
use App\Models\ContactUs;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ContactUsCrudController
 *
 * @property-read CrudPanel $crud
 */
class ContactUsCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(ContactUs::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/contact-us');
        CRUD::setEntityNameStrings('رسالة تواصل', 'رسائل التواصل');

        if (! backpack_user()->can('customer.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        foreach (['create', 'update', 'delete'] as $operation) {
            if (backpack_user()->can("customer.{$operation}")) {
                $this->crud->allowAccess($operation);
            }
        }
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
            'name' => 'name',
            'label' => 'الاسم',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => 'البريد الإلكتروني',
            'type' => 'email',
        ]);

        CRUD::addColumn([
            'name' => 'phone',
            'label' => 'رقم الهاتف',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'subject',
            'label' => 'الموضوع',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'message',
            'label' => 'الرسالة',
            'type' => 'textarea',
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'تاريخ الإرسال',
            'type' => 'datetime',
        ]);

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ContactUsRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

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
}
