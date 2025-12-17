<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class AirbnbBookingController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Booking::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/airbnb-booking');
        CRUD::setEntityNameStrings(__('cms.airbnb_booking_management'), __('cms.airbnb_booking_management'));
        CRUD::denyAccess(['create', 'update']);
        
        // إظهار حجوزات Airbnb فقط
        $this->crud->query->where('is_airbnb_booking', 1);
        
        if (!backpack_user()->can('booking.list')) {
            abort(403, 'Unauthorized Access - List');
        }
        
        $this->crud->removeButton('create');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // إضافة فلتر للحالة
        $this->addStatusFilter();
        
        // إضافة فلتر لحالة الدفع
        $this->addPaymentStatusFilter();
        
        // عمود الشقة
        CRUD::addColumn([
            'name' => 'apartment_id',
            'type' => 'select',
            'label' => __('cms.apartment') . ' <i class="la la-home"></i>',
            'entity' => 'apartment',
            'attribute' => 'name_ar',
            'model' => \App\Models\Apartment::class,
        ]);

        // عمود المبنى
        CRUD::addColumn([
            'name' => 'building_id',
            'type' => 'select',
            'label' => __('cms.building') . ' <i class="la la-building"></i>',
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
        ]);

        // عمود اسم العميل
        CRUD::addColumn([
            'name' => 'customer_full_name',
            'label' => __('cms.customer_name') . ' <i class="la la-user"></i>',
            'type' => 'text',
        ]);

        // عمود البريد الإلكتروني
        CRUD::addColumn([
            'name' => 'customer_email',
            'label' => __('cms.email') . ' <i class="la la-envelope"></i>',
            'type' => 'text',
        ]);

        // عمود تاريخ الوصول
        CRUD::addColumn([
            'name' => 'check_in',
            'label' => __('cms.check_in') . ' <i class="la la-calendar"></i>',
            'type' => 'date',
        ]);

        // عمود تاريخ المغادرة
        CRUD::addColumn([
            'name' => 'check_out',
            'label' => __('cms.check_out') . ' <i class="la la-calendar"></i>',
            'type' => 'date',
        ]);

        // عمود عدد الليالي
        CRUD::addColumn([
            'name' => 'number_of_nights',
            'label' => __('cms.nights') . ' <i class="la la-moon"></i>',
            'type' => 'number',
        ]);

        // عمود الحالة
        CRUD::addColumn([
            'name' => 'status',
            'label' => __('cms.status') . ' <i class="la la-info-circle"></i>',
            'type' => 'custom_html',
            'value' => function($entry) {
                return $this->getStatusBadge($entry->status);
            }
        ]);

        // عمود حالة الدفع
        CRUD::addColumn([
            'name' => 'payment_status',
            'label' => __('cms.payment_status') . ' <i class="la la-credit-card"></i>',
            'type' => 'custom_html',
            'value' => function($entry) {
                return $this->getPaymentStatusBadge($entry->payment_status);
            }
        ]);

        // عمود معرف الحجز
        CRUD::addColumn([
            'name' => 'number_of_booking',
            'label' => __('cms.booking_number') . ' <i class="la la-hashtag"></i>',
            'type' => 'text',
        ]);

        // عمود مصدر الحجز
        CRUD::addColumn([
            'name' => 'booking_source',
            'type' => 'custom_html',
            'label' => 'مصدر الحجز <i class="la la-source"></i>',
            'value' => function($entry) {
                $sourceIcons = [
                    'web' => '<i class="la la-globe"></i>',
                    'android' => '<i class="la la-android"></i>',
                    'ios' => '<i class="la la-apple"></i>',
                    'ownerrez' => '<i class="la la-link"></i>',
                    'airbnb' => '<i class="la la-home"></i>',
                    'booking_com' => '<i class="la la-bed"></i>',
                    'guesty' => '<i class="la la-building"></i>',
                    'other' => '<i class="la la-question-circle"></i>',
                ];
                
                $sourceLabels = [
                    'web' => 'ويب',
                    'android' => 'أندرويد',
                    'ios' => 'iOS',
                    'ownerrez' => 'OwnerRez',
                    'airbnb' => 'Airbnb',
                    'booking_com' => 'Booking',
                    'guesty' => 'Guesty',
                    'other' => 'أخرى',
                ];
                
                $sourceColors = [
                    'web' => 'primary',
                    'android' => 'success',
                    'ios' => 'dark',
                    'ownerrez' => 'info',
                    'airbnb' => 'danger',
                    'booking_com' => 'primary',
                    'guesty' => 'warning',
                    'other' => 'secondary',
                ];
                
                $bookingSource = $entry->booking_source ?? 'airbnb';
                $icon = $sourceIcons[$bookingSource] ?? $sourceIcons['other'];
                $label = $sourceLabels[$bookingSource] ?? ucfirst($bookingSource);
                $color = $sourceColors[$bookingSource] ?? 'secondary';
                
                return "<span class='badge badge-{$color}'>{$icon} {$label}</span>";
            }
        ]);

        // عمود تاريخ الإنشاء
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('cms.created_at') . ' <i class="la la-clock"></i>',
            'type' => 'datetime',
        ]);
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        
        // معلومات الحجز
        CRUD::addColumn([
            'name' => 'معلومات الحجز',
            'type' => 'custom_html',
            'value' => function($entry) {
                $sourceLabels = [
                    'web' => 'الموقع الإلكتروني',
                    'android' => 'أندرويد',
                    'ios' => 'آيفون',
                    'ownerrez' => 'OwnerRez',
                    'airbnb' => 'Airbnb',
                    'booking_com' => 'Booking.com',
                    'guesty' => 'Guesty',
                    'other' => 'أخرى',
                ];
                $bookingSource = $entry->booking_source ?? 'airbnb';
                $sourceLabel = $sourceLabels[$bookingSource] ?? ucfirst($bookingSource);
                
                return '<div class="card">
                    <div class="card-header">
                        <h4><i class="la la-info-circle"></i> معلومات الحجز</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>رقم الحجز:</strong> ' . $entry->number_of_booking . '</p>
                        <p><strong>مصدر الحجز:</strong> <span class="badge badge-info">' . $sourceLabel . '</span></p>
                        <p><strong>الحالة:</strong> ' . $this->getStatusBadge($entry->status) . '</p>
                        <p><strong>حالة الدفع:</strong> ' . $this->getPaymentStatusBadge($entry->payment_status) . '</p>
                        <p><strong>تاريخ الإنشاء:</strong> ' . $entry->created_at->format('Y-m-d H:i:s') . '</p>
                    </div>
                </div>';
            }
        ]);

        // معلومات العميل
        CRUD::addColumn([
            'name' => 'معلومات العميل',
            'type' => 'custom_html',
            'value' => function($entry) {
                return '<div class="card">
                    <div class="card-header">
                        <h4><i class="la la-user"></i> معلومات العميل</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>الاسم:</strong> ' . $entry->customer_full_name . '</p>
                        <p><strong>البريد الإلكتروني:</strong> ' . $entry->customer_email . '</p>
                        <p><strong>عدد البالغين:</strong> ' . $entry->adults_count . '</p>
                        <p><strong>عدد الأطفال:</strong> ' . $entry->children_count . '</p>
                    </div>
                </div>';
            }
        ]);

        // معلومات الشقة
        CRUD::addColumn([
            'name' => 'معلومات الشقة',
            'type' => 'custom_html',
            'value' => function($entry) {
                return '<div class="card">
                    <div class="card-header">
                        <h4><i class="la la-home"></i> معلومات الشقة</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>اسم الشقة:</strong> ' . $entry->apartment->name_ar . '</p>
                        <p><strong>المبنى:</strong> ' . $entry->apartment->building->name_ar . '</p>
                        <p><strong>تاريخ الوصول:</strong> ' . $entry->check_in . '</p>
                        <p><strong>تاريخ المغادرة:</strong> ' . $entry->check_out . '</p>
                        <p><strong>عدد الليالي:</strong> ' . $entry->number_of_nights . '</p>
                    </div>
                </div>';
            }
        ]);
    }

    /**
     * إضافة فلتر للحالة
     */
    private function addStatusFilter()
    {
        CRUD::addFilter([
            'name' => 'status',
            'type' => 'dropdown',
            'label' => __('cms.status'),
        ], [
            'pending' => __('cms.pending'),
            'approved' => __('cms.approved'),
            'cancelled' => __('cms.cancelled'),
            'booked' => __('cms.booked'),
        ], function($value) {
            $this->crud->addClause('where', 'status', $value);
        });
    }

    /**
     * إضافة فلتر لحالة الدفع
     */
    private function addPaymentStatusFilter()
    {
        CRUD::addFilter([
            'name' => 'payment_status',
            'type' => 'dropdown',
            'label' => __('cms.payment_status'),
        ], [
            'pending' => __('cms.pending'),
            'paid' => __('cms.paid'),
            'failed' => __('cms.failed'),
        ], function($value) {
            $this->crud->addClause('where', 'payment_status', $value);
        });
    }

    /**
     * إنشاء شارة الحالة
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">معلق</span>',
            'approved' => '<span class="badge badge-success">موافق</span>',
            'cancelled' => '<span class="badge badge-danger">ملغي</span>',
            'booked' => '<span class="badge badge-info">محجوز</span>',
        ];
        
        return $badges[$status] ?? '<span class="badge badge-secondary">غير محدد</span>';
    }

    /**
     * إنشاء شارة حالة الدفع
     */
    private function getPaymentStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">معلق</span>',
            'paid' => '<span class="badge badge-success">مدفوع</span>',
            'failed' => '<span class="badge badge-danger">فشل</span>',
        ];
        
        return $badges[$status] ?? '<span class="badge badge-secondary">غير محدد</span>';
    }
}

