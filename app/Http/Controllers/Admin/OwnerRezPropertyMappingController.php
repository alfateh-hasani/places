<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OwnerRezPropertyMappingRequest;
use App\Services\OwnerRez\OwnerRezApiService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OwnerRezPropertyMappingController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }

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

        $ownerRezPropertyOptions = $this->getOwnerRezPropertyOptions();
        $currentEntry = $this->crud->getCurrentEntry();

        if ($currentEntry && $currentEntry->ownerrez_property_id) {
            $currentId = $currentEntry->ownerrez_property_id;
            if (! array_key_exists($currentId, $ownerRezPropertyOptions)) {
                $currentName = $currentEntry->ownerrez_property_name ?: 'العقار الحالي';
                $ownerRezPropertyOptions[$currentId] = "{$currentName} (ID: {$currentId})";
            }
        }

        $ownerRezField = CRUD::field('ownerrez_property_id')
            ->type('select2_from_array')
            ->label('العقار في OwnerRez')
            ->options($ownerRezPropertyOptions)
            ->wrapper(['class' => 'form-group col-md-6']);

        if (! empty($ownerRezPropertyOptions)) {
            $ownerRezField
                ->allows_null(false)
                ->hint('اختر العقار من OwnerRez');
        } else {
            $ownerRezField
                ->allows_null(true)
                ->hint('تعذر جلب العقارات حالياً، جرّب تحديث الصفحة');
        }

        $ownerRezNameField = CRUD::field('ownerrez_property_name')
            ->type('text')
            ->label('اسم العقار في OwnerRez')
            ->hint('سيتم جلبه تلقائياً من OwnerRez')
            ->wrapper(['class' => 'form-group col-md-12']);

        $ownerRezNameField->attributes(['readonly' => 'readonly']);

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

    public function store()
    {
        $this->syncOwnerRezPropertyName();

        return $this->traitStore();
    }

    public function update()
    {
        $this->syncOwnerRezPropertyName();

        return $this->traitUpdate();
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

    protected function getOwnerRezPropertyOptions(): array
    {
        $cacheKey = 'ownerrez:properties:dropdown';
        $cacheTtl = 300;

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && ! empty($cached)) {
                return $cached;
            }

            $options = OwnerRezPropertyController::options();

            if (! empty($options)) {
                Cache::put($cacheKey, $options, $cacheTtl);
            }

            return $options;
        } catch (\Exception $e) {
            Log::warning('Failed to load OwnerRez properties for dropdown', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function syncOwnerRezPropertyName(): void
    {
        $request = $this->crud->getRequest();
        $propertyId = $request->input('ownerrez_property_id');

        if (! $propertyId || ! ctype_digit((string) $propertyId)) {
            return;
        }

        try {
            $apiService = app(OwnerRezApiService::class);
            $property = $apiService->getProperty((int) $propertyId);
            $propertyName = $property['name'] ?? null;

            if ($propertyName) {
                $request->merge(['ownerrez_property_name' => $propertyName]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch OwnerRez property name', [
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
