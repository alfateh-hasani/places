<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ServiceBookingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ServiceBookingCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ServiceBookingCrudController extends CrudController
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
        CRUD::setModel(\App\Models\ServiceBooking::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/service-booking');
        CRUD::setEntityNameStrings('طلب خدمة', 'طلبات الخدمات');
        CRUD::denyAccess(['create', 'delete']);
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
            'name' => 'id',
            'label' => 'رقم الطلب',
            'type' => 'text'
        ]);
    
        CRUD::addColumn([
            'name' => 'booking_id',
            'label' => 'رقم الحجز',
            'type' => 'text'
        ]);
    
        CRUD::addColumn([
            'name' => 'apartment',
            'label' => 'الشقة',
            'type' => 'relationship',
            'entity' => 'apartment',
            'attribute' => 'name_ar',
            'model' => 'App\Models\Apartment',
        ]);

        CRUD::addColumn([
            'name' => 'apartment.building',
            'label' => 'المبنى',
            'type' => 'relationship',
            'entity' => 'apartment.building',
            'attribute' => 'name_ar',
            'model' => 'App\Models\Building',
        ]);
    
        CRUD::addColumn([
            'name' => 'customer',
            'label' => 'العميل',
            'type' => 'relationship',
            'entity' => 'customer',
            'attribute' => 'full_name',
            'model' => 'App\Models\Customer',
        ]);
    
        CRUD::addColumn([
            'name' => 'service',
            'label' => 'الخدمة',
            'type' => 'relationship',
            'entity' => 'service',
            'attribute' => 'name_ar',
            'model' => 'App\Models\Service',
        ]);
    
        CRUD::addColumn([
            'name' => 'price',
            'label' => 'السعر',
            'type' => 'number',
            'suffix' => ' ر.س'
        ]);
    
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'الحالة',
            'type' => 'select_from_array',
            'options' => [
                'pending' => 'قيد التنفيذ',
                'completed' => 'مكتمل'
            ],
        ]);
    
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'تاريخ الطلب',
            'type' => 'datetime'
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
        CRUD::setValidation(ServiceBookingRequest::class);
        
        CRUD::addField([
            'name' => 'status',
            'label' => 'تحديث الحالة',
            'type' => 'select_from_array',
            'options' => [
                'pending' => 'قيد التنفيذ',
                'completed' => 'مكتمل'
            ],
            'allows_null' => false,
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
}
