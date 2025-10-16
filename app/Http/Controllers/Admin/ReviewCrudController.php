<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ReviewRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ReviewCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ReviewCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Review::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/review');
        CRUD::setEntityNameStrings('مراجعة', 'مراجعات العملاء');
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
            'label' => 'رقم المراجعة',
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'customer',
            'label' => 'اسم العميل',
            'type' => 'relationship',
            'entity' => 'customer',
            'attribute' => 'full_name',
            'model' => 'App\Models\Customer',
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
            'name' => 'rating',
            'label' => 'التقييم',
            'type' => 'number',
            'suffix' => ' ⭐',
        ]);

        CRUD::addColumn([
            'name' => 'review_text',
            'label' => 'نص المراجعة',
            'type' => 'textarea'
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'تاريخ المراجعة',
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
        CRUD::setValidation(ReviewRequest::class);
        CRUD::addField([
            'name' => 'customer_id',
            'label' => 'العميل',
            'type' => 'select2',
            'entity' => 'customer',
            'attribute' => 'full_name',
            'model' => 'App\Models\Customer',
        ]);

        CRUD::addField([
            'name' => 'apartment_id',
            'label' => 'الشقة',
            'type' => 'select2',
            'entity' => 'apartment',
            'attribute' => 'name_ar',
            'model' => 'App\Models\Apartment',
        ]);

        CRUD::addField([
            'name' => 'rating',
            'label' => 'التقييم',
            'type' => 'number',
            'attributes' => [
                'min' => 1,
                'max' => 5
            ]
        ]);

        CRUD::addField([
            'name' => 'review_text',
            'label' => 'نص المراجعة',
            'type' => 'textarea'
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
