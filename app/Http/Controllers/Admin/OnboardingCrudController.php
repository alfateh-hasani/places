<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OnboardingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class OnboardingCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OnboardingCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Onboarding::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/onboarding');
        CRUD::setEntityNameStrings('عنصر تعريفي', 'عناصر التعريف');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('id')->label('المعرف');
        CRUD::column('title_ar')->label('العنوان بالعربية');
        CRUD::column('title_en')->label('العنوان بالإنجليزية');
        CRUD::column('description_ar')->label('الوصف بالعربية')->limit(50);
        CRUD::column('description_en')->label('الوصف بالإنجليزية')->limit(50);
        CRUD::column('order')->label('الترتيب');
        
        CRUD::addColumn([
            'name' => 'image',
            'type' => 'image',
            'label' => 'الصورة',
            'height' => '50px',
            'width' => '50px',
        ]);
        
        CRUD::column('created_at')->label('تاريخ الإنشاء');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(OnboardingRequest::class);

        CRUD::field('title_ar')
            ->label('العنوان بالعربية')
            ->type('text')
            ->attributes(['required' => true]);

        CRUD::field('title_en')
            ->label('العنوان بالإنجليزية')
            ->type('text')
            ->attributes(['required' => true]);

        CRUD::field('description_ar')
            ->label('الوصف بالعربية')
            ->type('textarea')
            ->attributes(['rows' => 4]);

        CRUD::field('description_en')
            ->label('الوصف بالإنجليزية')
            ->type('textarea')
            ->attributes(['rows' => 4]);

        CRUD::field('order')
            ->label('الترتيب')
            ->type('number')
            ->default(0)
            ->attributes(['min' => 0]);

        CRUD::field('image')
            ->label('الصورة')
            ->type('image')
            ->withMedia(['collection' => 'image'])
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
}
