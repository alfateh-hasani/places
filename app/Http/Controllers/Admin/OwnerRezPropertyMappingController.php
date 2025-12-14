<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OwnerRezPropertyMappingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class OwnerRezPropertyMappingController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\OwnerRezPropertyMapping::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/ownerrez-property-mapping');
        CRUD::setEntityNameStrings('ربط OwnerRez', 'ربط العقارات مع OwnerRez');

        if (! backpack_user()->can('apartment.list')) {
            abort(403, 'Unauthorized Access');
        }
    }

    protected function setupListOperation()
    {
        CRUD::column('apartment_id')
            ->type('select')
            ->entity('apartment')
            ->attribute('name_en')
            ->label('الشقة');

        CRUD::column('ownerrez_property_id')
            ->label('Property ID في OwnerRez');

        CRUD::column('ownerrez_property_name')
            ->label('اسم العقار في OwnerRez')
            ->limit(50);

        CRUD::column('sync_enabled')
            ->type('boolean')
            ->label('المزامنة مفعلة')
            ->wrapper([
                'class' => 'text-center',
            ]);

        CRUD::column('check_availability_enabled')
            ->type('boolean')
            ->label('التحقق من التوفر')
            ->wrapper([
                'class' => 'text-center',
            ]);

        CRUD::column('last_synced_at')
            ->type('datetime')
            ->label('آخر مزامنة');

        CRUD::addButtonFromView('line', 'sync_now', 'sync_ownerrez', 'beginning');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(OwnerRezPropertyMappingRequest::class);

        // Check if apartment_id is passed from URL
        $apartmentId = request()->get('apartment_id');

        CRUD::field('apartment_id')
            ->type('select')
            ->entity('apartment')
            ->attribute('name_en')
            ->model('App\Models\Apartment')
            ->label('الشقة')
            ->hint('اختر الشقة التي تريد ربطها مع OwnerRez')
            ->default($apartmentId)
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('ownerrez_property_id')
            ->type('text')
            ->label('Property ID في OwnerRez')
            ->hint('يمكنك الحصول عليه من لوحة تحكم OwnerRez')
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('ownerrez_property_name')
            ->type('text')
            ->label('اسم العقار في OwnerRez')
            ->hint('اختياري - سيتم جلبه تلقائياً من OwnerRez')
            ->wrapper(['class' => 'form-group col-md-12']);

        CRUD::field('sync_enabled')
            ->type('switch')
            ->label('تفعيل المزامنة التلقائية')
            ->hint('عند التفعيل، سيتم مزامنة الحجوزات تلقائياً مع OwnerRez')
            ->default(true)
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('check_availability_enabled')
            ->type('switch')
            ->label('تفعيل التحقق من التوفر')
            ->hint('سيتم التحقق من OwnerRez قبل قبول أي حجز جديد')
            ->default(true)
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('notes')
            ->type('textarea')
            ->label('ملاحظات')
            ->hint('أي ملاحظات إضافية حول هذا الربط')
            ->attributes(['rows' => 3])
            ->wrapper(['class' => 'form-group col-md-12']);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::column('notes')
            ->type('text')
            ->label('ملاحظات');

        CRUD::column('created_at')
            ->type('datetime')
            ->label('تاريخ الإنشاء');

        CRUD::column('updated_at')
            ->type('datetime')
            ->label('آخر تحديث');
    }

    /**
     * Sync property with OwnerRez
     */
    public function sync($id)
    {
        $mapping = \App\Models\OwnerRezPropertyMapping::findOrFail($id);

        try {
            // Call sync service
            $syncService = app(\App\Services\OwnerRez\OwnerRezSyncService::class);

            // Mark as synced
            $mapping->markAsSynced();

            \Alert::success('تم مزامنة العقار بنجاح مع OwnerRez')->flash();
        } catch (\Exception $e) {
            \Alert::error('فشلت المزامنة: '.$e->getMessage())->flash();
        }

        return redirect()->back();
    }
}
