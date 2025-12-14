<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ApartmentRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApartmentController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ApartmentController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\Pro\Http\Controllers\Operations\DropzoneOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Apartment::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/apartment');
        CRUD::setEntityNameStrings(__('cms.apartment'), __('cms.apartments'));
        if (! backpack_user()->can('apartment.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        if (backpack_user()->can('apartment.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('apartment.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('apartment.delete')) {
            $this->crud->allowAccess('delete');
        }
        if (backpack_user()->hasRole('supervisor')) {
            $this->crud->query->whereHas('building', function ($query) {
                $query->where('supervisor_id', backpack_user()->id);
            });
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
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'Name',
        ]);
        CRUD::addColumn([
            'name' => 'building_id',
            'type' => 'select',
            'label' => __('cms.building'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
        ]);

        CRUD::addColumn([
            'name' => 'price',
            'type' => 'number',
            'label' => __('cms.price'),
        ]);
        CRUD::addColumn([
            'name' => 'is_active',
            'type' => 'boolean',
            'label' => __('cms.is_active'),
        ]);
        CRUD::addColumn([
            'name' => 'smart_lock_id',
            'type' => 'select',
            'label' => __('cms.lock'),
            'entity' => 'lock',
            'attribute' => 'lock_id',
            'model' => \App\Models\SmartLock::class,
        ]);

        CRUD::addColumn([
            'name' => 'image_view',
            'type' => 'image',
            'label' => __('cms.image'),
            'height' => '50px',
            'width' => '50px',
        ]);

        // عمود حالة ربط OwnerRez
        CRUD::addColumn([
            'name' => 'ownerrez_status',
            'label' => 'OwnerRez',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->ownerrezMapping) {
                    $badge = $entry->ownerrezMapping->sync_enabled
                        ? '<span class="badge badge-success">مربوط ✓</span>'
                        : '<span class="badge badge-warning">معطل</span>';

                    return $badge;
                }

                return '<span class="badge badge-secondary">غير مربوط</span>';
            },
            'escaped' => false,
        ]);

        CRUD::addButtonFromModelFunction('line', 'calendar_button', 'getCalendarButton', 'beginning');
        CRUD::addButtonFromModelFunction('line', 'pricing_button', 'getPricingButton', 'beginning');
        // add button for copy link
        CRUD::addButtonFromModelFunction('line', 'copy_link_button', 'getCopyLinkButton', 'beginning');
        // زر ربط مع OwnerRez
        CRUD::addButtonFromView('line', 'link_to_ownerrez', 'link_to_ownerrez', 'beginning');

        // إضافة فلتر حسب المشروع
        CRUD::filter('select2')
            ->type('select2')
            ->label('المشروع')
            ->values(function () {
                return \App\Models\Building::all()->pluck('name_ar', 'id')->toArray();
            })
            ->whenActive(function ($value) {
                $this->crud->addClause('where', 'building_id', $value);
            });
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
        CRUD::setValidation(ApartmentRequest::class);
        CRUD::field('image')
            ->label(__('cms.image'))
            ->type('dropzone')
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
            ])->wrapperAttributes([
                'class' => 'form-group col-md-12',
            ]);

        $this->crud->addField([
            'name' => 'is_active',
            'type' => 'select_from_array',
            'label' => __('cms.is_active'),
            'options' => [1 => __('cms.yes'), 0 => __('cms.no')],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' => __('cms.name_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'apart_no',
            'type' => 'text',
            'label' => __('cms.apart_no'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'building_id',
            'type' => 'select2',
            'label' => __('cms.building'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.building_select2'),
        ]);

        $this->crud->addField([
            'name' => 'smart_lock_id',
            'type' => 'select2_smart_lock',
            'label' => __('cms.lock'),
            'entity' => 'lock',
            'attribute' => 'full_name',
            'model' => \App\Models\SmartLock::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'data_source' => url('admin/get-smart-locks'),
            'placeholder' => __('cms.lock_select2'),
            'minimum_input_length' => 0,
            'dependencies' => ['building_id'],
            'loading' => true,
        ]);

        $this->crud->addField([
            'name' => 'num_rooms',
            'type' => 'number',
            'label' => __('cms.num_rooms'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'num_beds',
            'type' => 'number',
            'label' => __('cms.num_beds'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        // bathrooms_count
        $this->crud->addField([
            'name' => 'bathrooms_count',
            'type' => 'number',
            'label' => __('cms.bathrooms_count'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        // adults_count
        $this->crud->addField([
            'name' => 'adults_count',
            'type' => 'number',
            'label' => __('cms.adults_count'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        // children_count
        $this->crud->addField([
            'name' => 'children_count',
            'type' => 'number',
            'label' => __('cms.children_count'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'area',
            'type' => 'number',
            'label' => __('cms.area'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'floor_number',
            'type' => 'number',
            'label' => __('cms.floor_number'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'unit_number',
            'type' => 'number',
            'label' => __('cms.unit_number'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'features',
            'type' => 'select2_multiple',
            'label' => __('cms.features'),
            'entity' => 'features',
            'attribute' => 'name_ar',
            'model' => \App\Models\Feature::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.features_select2'),
        ]);
        $this->crud->addField([
            'name' => 'policy_id',
            'type' => 'select2',
            'label' => __('cms.policy'),
            'entity' => 'policy',
            'attribute' => 'name_ar',
            'model' => \App\Models\Policy::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.policy_select2'),
        ]);

        $this->crud->addField([
            'name' => 'price',
            'type' => 'number',
            'label' => __('cms.price').' (السعر القديم - للتوافق فقط)',
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'hint' => 'يُستخدم كقيمة افتراضية إذا لم يتم تحديد أسعار متقدمة',
        ]);

        // ═══════════════════════════════════════════════
        // 🎯 حقول التسعير المتقدم (PricingService)
        // ═══════════════════════════════════════════════

        $this->crud->addField([
            'name' => 'pricing_section',
            'type' => 'custom_html',
            'value' => '<hr><h4 class="text-primary"><i class="la la-dollar"></i> إعدادات التسعير المتقدم</h4>',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        // السعر الأساسي
        $this->crud->addField([
            'name' => 'base_price',
            'type' => 'number',
            'label' => 'السعر الأساسي لليلة (SAR)',
            'attributes' => [
                'step' => '0.01',
                'min' => '0',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4',
            ],
            'hint' => 'السعر الافتراضي لليلة الواحدة في أيام الأسبوع',
        ]);

        // سعر نهاية الأسبوع
        $this->crud->addField([
            'name' => 'weekend_price',
            'type' => 'number',
            'label' => 'سعر نهاية الأسبوع (SAR)',
            'attributes' => [
                'step' => '0.01',
                'min' => '0',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4',
            ],
            'hint' => 'السعر الخاص بليالي الجمعة والسبت (اتركه فارغاً لاستخدام السعر الأساسي)',
        ]);

        // خصم الإقامة الطويلة
        $this->crud->addField([
            'name' => 'long_stay_discount',
            'type' => 'number',
            'label' => 'خصم الإقامة الطويلة (%)',
            'attributes' => [
                'step' => '0.01',
                'min' => '0',
                'max' => '100',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4',
            ],
            'hint' => 'نسبة الخصم للحجوزات 7 ليالي فأكثر',
        ]);

        $this->crud->addField([
            'name' => 'pricing_note',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info">
                <i class="la la-info-circle"></i> 
                <strong>ملاحظة:</strong> الأولوية للأسعار: 
                <strong>1)</strong> سعر التقويم المخصص 
                <strong>2)</strong> سعر نهاية الأسبوع 
                <strong>3)</strong> السعر الأساسي
            </div>',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        $this->crud->addField([
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'description_ar',
            'type' => 'ckeditor',
            'label' => __('cms.description_ar'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        $this->crud->addField([
            'name' => 'description_en',
            'type' => 'ckeditor',
            'label' => __('cms.description_en'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        // seo_title_ar
        $this->crud->addField([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        // seo_title_en
        $this->crud->addField([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        // seo_description_ar
        $this->crud->addField([
            'name' => 'seo_description_ar',
            'type' => 'text',
            'label' => __('cms.seo_description_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        // seo_description_en
        $this->crud->addField([
            'name' => 'seo_description_en',
            'type' => 'text',
            'label' => __('cms.seo_description_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        // Label
        $this->crud->addField([
            'name' => 'labels',
            'type' => 'select2_multiple',
            'label' => __('cms.apartment_label'),
            'entity' => 'labels',
            'attribute' => 'name_ar',
            'model' => \App\Models\ApartmentLabel::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.label_select2'),
        ]);
        $this->crud->addField([
            'name' => 'category_id',
            'type' => 'select2',
            'label' => __('cms.category_2'),
            'entity' => 'category',
            'attribute' => 'name',
            'model' => \App\Models\Category::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.category_select2'),
        ]);

        $this->crud->addField([
            'name' => 'ics_url',
            'label' => 'ICS URL',
            'type' => 'url',
            'hint' => 'أدخل رابط ICS (AIRBNB).',
        ]);

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

        // تحميل بيانات التسعير الحالية
        $entry = $this->crud->getCurrentEntry();
        if ($entry) {
            $entry->load('pricing');

            if ($entry->pricing) {
                // تعيين القيم الافتراضية للحقول
                CRUD::field('base_price')->value($entry->pricing->base_price);
                CRUD::field('weekend_price')->value($entry->pricing->weekend_price);
                CRUD::field('long_stay_discount')->value($entry->pricing->long_stay_discount);
            }
        }
    }

    /**
     * Override store method to save pricing data
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        // إزالة حقول التسعير من البيانات قبل الحفظ
        $strippedRequest = $this->crud->getStrippedSaveRequest($request);
        $pricingData = [
            'base_price' => $strippedRequest['base_price'] ?? null,
            'weekend_price' => $strippedRequest['weekend_price'] ?? null,
            'long_stay_discount' => $strippedRequest['long_stay_discount'] ?? null,
        ];
        unset($strippedRequest['base_price'], $strippedRequest['weekend_price'], $strippedRequest['long_stay_discount']);

        // حفظ الشقة بدون حقول التسعير
        $item = $this->crud->create($strippedRequest);
        $this->data['entry'] = $this->crud->entry = $item;

        // حفظ بيانات التسعير في جدول منفصل
        $this->savePricingDataFromArray($item, $pricingData);

        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Override update method to save pricing data
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        // إزالة حقول التسعير من البيانات قبل الحفظ
        $strippedRequest = $this->crud->getStrippedSaveRequest($request);
        $pricingData = [
            'base_price' => $strippedRequest['base_price'] ?? null,
            'weekend_price' => $strippedRequest['weekend_price'] ?? null,
            'long_stay_discount' => $strippedRequest['long_stay_discount'] ?? null,
        ];
        unset($strippedRequest['base_price'], $strippedRequest['weekend_price'], $strippedRequest['long_stay_discount']);

        // تحديث الشقة بدون حقول التسعير
        $item = $this->crud->update($this->crud->getCurrentEntryId(), $strippedRequest);
        $this->data['entry'] = $this->crud->entry = $item;

        // حفظ بيانات التسعير في جدول منفصل
        $this->savePricingDataFromArray($item, $pricingData);

        \Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * حفظ بيانات التسعير من مصفوفة
     */
    protected function savePricingDataFromArray($entry, $data)
    {
        $pricingData = [
            'apartment_id' => $entry->id,
            'base_price' => $data['base_price'] ?: $entry->price,
            'weekend_price' => $data['weekend_price'] ?: null,
            'long_stay_discount' => $data['long_stay_discount'] ?: null,
            'is_active' => 1,
        ];

        // تحديث أو إنشاء سجل التسعير
        \App\Models\ApartmentPrice::updateOrCreate(
            ['apartment_id' => $entry->id],
            $pricingData
        );
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::addColumn([
            'name' => 'description_ar',
            'type' => 'text',
            'label' => __('cms.description_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'limit' => 10000000,
        ]);
        CRUD::addColumn([
            'name' => 'description_en',
            'type' => 'text',
            'label' => __('cms.description_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'limit' => 10000000,
        ]);

    }
}
