<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class CategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CategoryCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Category::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/category');
        CRUD::setEntityNameStrings( 'التصنيف','التصنيفات');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'name' => 'name',
            'type' =>  'text',
            'label' => __('cms.name'),
        ]);
        
        $this->crud->addColumn([
            'name' => 'price',
            'type' =>  'number',
            'label' => __('cms.price') . ' (السعر الأساسي)',
            'suffix' => ' ر.س',
        ]);
        
        $this->crud->addColumn([
            'name' => 'weekend_price',
            'type' => 'number',
            'label' => 'سعر نهاية الأسبوع',
            'suffix' => ' ر.س',
        ]);
        
        $this->crud->addColumn([
            'name' => 'long_stay_discount',
            'type' => 'number',
            'label' => 'خصم الإقامة الطويلة',
            'suffix' => '%',
        ]);
        
        $this->crud->addColumn([
            'name' => 'apartments_count',
            'type' => 'custom_html',
            'label' => 'عدد الشقق',
            'value' => function($entry) {
                $count = $entry->apartments()->count();
                return '<span class="badge badge-info">' . $count . ' شقة</span>';
            },
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
        $this->crud->addField([
            'name' => 'name',
            'type' =>  'text',
            'label' => __('cms.name'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'price',
            'type' =>  'number',
            'label' => __('cms.price') . ' (السعر الأساسي)',
            'attributes' => [
                'step' => '0.01',
                'min' => '0',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'hint' => 'عند تغيير هذا السعر، سيتم تحديث السعر الأساسي لجميع الشقق التابعة لهذا التصنيف',
        ]);
        
        $this->crud->addField([
            'name' => 'weekend_price',
            'type' => 'number',
            'label' => 'سعر نهاية الأسبوع',
            'attributes' => [
                'step' => '0.01',
                'min' => '0',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'hint' => 'السعر المطبق في عطلة نهاية الأسبوع (اختياري)',
        ]);
        
        $this->crud->addField([
            'name' => 'long_stay_discount',
            'type' => 'number',
            'label' => 'خصم الإقامة الطويلة',
            'attributes' => [
                'step' => '1',
                'min' => '0',
                'max' => '100',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'hint' => 'نسبة الخصم للإقامة الطويلة (0-100%)',
        ]);
        
        $this->crud->addField([
            'name' => 'pricing_info',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info">
                <i class="la la-info-circle"></i> 
                <strong>ملاحظة هامة:</strong> 
                عند تحديث الأسعار في هذا التصنيف، سيتم تلقائياً:
                <ul class="mb-0 mt-2">
                    <li>تحديث <strong>السعر الأساسي</strong> لجميع الشقق التابعة لهذا التصنيف</li>
                    <li>تحديث <strong>سعر نهاية الأسبوع</strong> لجميع الشقق التابعة لهذا التصنيف</li>
                    <li>تحديث <strong>خصم الإقامة الطويلة</strong> لجميع الشقق التابعة لهذا التصنيف</li>
                    <li>الأسعار المخصصة في التقويم لن تتأثر</li>
                </ul>
            </div>',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
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
