<?php

namespace App\Http\Controllers\Admin;

use App\Services\ScienerLockService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\DB;

/**
 * Class ApartmentController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BookingController extends CrudController
{
    // use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Booking::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/booking');
        CRUD::setEntityNameStrings(__('cms.booking_management'), __('cms.booking_management'));
        CRUD::denyAccess(['create', 'delete', 'update']);

        if (! backpack_user()->can('booking.list')) {
            abort(403, 'Unauthorized Access - List');
        }
        $this->crud->removeButton('create');
        // $this->crud->denyAccess(['create', 'update', 'delete']);

        if (backpack_user()->can('booking.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('booking.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('booking.delete')) {
            $this->crud->allowAccess('delete');
        }
        if (backpack_user()->hasRole('supervisor')) {
            $this->crud->query->whereHas('apartment', function ($query) {
                $query->whereHas('building', function ($query) {
                    $query->where('supervisor_id', backpack_user()->id);
                });
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
        $this->crud->enableExportButtons();
        // إخفاء حجوزات Airbnb من صفحة الحجوزات العادية
        $this->crud->query->where('is_airbnb_booking', '!=', 1);

        $this->addBuildingFilter();
        $this->addStatusFilter();
        $this->addPaymentStatusFilter();
        $this->addBookingSourceFilter();

        if (backpack_user()->can('booking.changeStatus')) {
            CRUD::addButtonFromModelFunction('line', 'changeStatus', 'getChangeStatusButton', 'end');
        }
        // if (backpack_user()->can('booking.changePaymentStatus')) {
        //     CRUD::addButtonFromModelFunction('line', 'changePaymentStatus', 'getChangePaymentStatusButton', 'end');
        // }

        // إضافة زر تعديل وقت الدخول باستخدام inline button
        if (backpack_user()->can('booking.changeStatus')) {
            CRUD::addButtonFromView('line', 'edit_check_in_time', 'edit_check_in_time', 'end');
        }
        // Customer column
        CRUD::addColumn([
            'name' => 'customer_id',
            'type' => 'select',
            'label' => __('cms.customer').' <i class="la la-user"></i>',
            'entity' => 'customer',
            'attribute' => 'first_name',
            'model' => \App\Models\Customer::class,
        ]);
        // Status with badge
        CRUD::addColumn([
            'name' => 'status',
            'label' => __('cms.status').' <i class="la la-info-circle"></i>',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return $this->getStatusBadge($entry->status);
            },
        ]);

        // Payment status with badge
        // CRUD::addColumn([
        //     'name' => 'payment_status',
        //     'label' => __('cms.payment_status') . ' <i class="la la-credit-card"></i>',
        //     'type' => 'custom_html',
        //     'value' => function($entry) {
        //         return $this->getPaymentStatusBadge($entry->payment_status);
        //     }
        // ]);

        // buliding column
        CRUD::addColumn([
            'name' => 'building_id',
            'type' => 'select',
            'label' => __('cms.building').' <i class="la la-building"></i>',
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
        ]);

        // Apartment column
        CRUD::addColumn([
            'name' => 'apartment_id',
            'type' => 'select',
            'label' => __('cms.apartment').' <i class="la la-building"></i>',
            'entity' => 'apartment',
            'attribute' => 'name_ar',
            'model' => \App\Models\Apartment::class,
        ]);

        // Number of Booking
        CRUD::addColumn([
            'name' => 'number_of_booking',
            'type' => 'text',
            'label' => __('cms.number_of_booking').' <i class="la la-bookmark"></i>',
        ]);

        // OwnerRez Booking ID
        CRUD::addColumn([
            'name' => 'ownerrez_booking_id',
            'type' => 'text',
            'label' => 'معرف OwnerRez <i class="la la-link"></i>',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('ownerrez_booking_id', 'like', '%'.$searchTerm.'%');
            },
        ]);

        // Booking Source
        CRUD::addColumn([
            'name' => 'booking_source',
            'type' => 'custom_html',
            'label' => 'مصدر الحجز <i class="la la-source"></i>',
            'value' => function ($entry) {
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

                $bookingSource = $entry->booking_source ?? 'web';
                $icon = $sourceIcons[$bookingSource] ?? $sourceIcons['other'];
                $label = $sourceLabels[$bookingSource] ?? ucfirst($bookingSource);
                $color = $sourceColors[$bookingSource] ?? 'secondary';

                return "<span class='badge badge-{$color}'>{$icon} {$label}</span>";
            },
        ]);

        // Booking date (created_at)
        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'custom_html',
            'label' => __('cms.booking_date').' <i class="la la-calendar-plus"></i>',
            'value' => function ($entry) {
                return '<span class="text-info font-weight-bold">'.\Carbon\Carbon::parse($entry->created_at)->format('Y-m-d H:i').'</span>';
            },
        ]);

        // Check-in date
        CRUD::addColumn([
            'name' => 'check_in',
            'type' => 'custom_html',
            'label' => __('cms.check_in').' <i class="la la-calendar-check"></i>',
            'value' => function ($entry) {
                return '<span class="text-success font-weight-bold">'.\Carbon\Carbon::parse($entry->check_in)->format('Y-m-d').'</span>';
            },
        ]);

        // Check-out date
        CRUD::addColumn([
            'name' => 'check_out',
            'type' => 'custom_html',
            'label' => __('cms.check_out').' <i class="la la-calendar-times"></i>',
            'value' => function ($entry) {
                return '<span class="text-danger font-weight-bold">'.\Carbon\Carbon::parse($entry->check_out)->format('Y-m-d').'</span>';
            },
        ]);

        // Number of nights
        CRUD::addColumn([
            'name' => 'number_of_nights',
            'type' => 'custom_html',
            'label' => __('cms.number_of_nights').' <i class="la la-moon"></i>',
            'value' => function ($entry) {
                return "<span class='badge badge-info'>{$entry->number_of_nights}</span>";
            },
        ]);

        // Total Price
        // CRUD::addColumn([
        //     'name' => 'total_price',
        //     'type' => 'custom_html',
        //     'label' => __('cms.total_price') . ' (SAR) <i class="la la-money"></i>',
        //     'value' => function($entry) {
        //         return '<span class="text-primary font-weight-bold">' . number_format($entry->total_price, 2) . ' SAR</span>';
        //     }
        // ]);

        // Final Price
        CRUD::addColumn([
            'name' => 'final_price',
            'type' => 'custom_html',
            'label' => 'المبلغ النهائية شامل الضريبة'.' (SAR) <i class="la la-money-bill"></i>',
            'value' => function ($entry) {
                return '<span class="text-success font-weight-bold">'.number_format($entry->final_price, 2).' SAR</span>';
            },
        ]);

        // Adults count

        // Payment Method
        CRUD::addColumn([
            'name' => 'payment_method_code',
            'type' => 'enum',
            'label' => __('cms.payment_method_code').' <i class="la la-wallet"></i>',
        ]);

        CRUD::addFilter(
            [
                'type' => 'date_range',
                'name' => 'from_to',
                'label' => __('cms.date_range'),
            ],
            false,
            function ($value) {
                // Skip if empty
                if (empty($value)) {
                    return;
                }

                $dates = json_decode($value);

                if (! isset($dates->from) || ! isset($dates->to)) {
                    return;
                }

                // Convert Arabic numerals → English (same as Transaction)
                $mapping = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                    '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

                $from = preg_replace_callback('/[٠-٩]/u', function ($m) use ($mapping) {
                    return $mapping[$m[0]];
                }, $dates->from);

                $to = preg_replace_callback('/[٠-٩]/u', function ($m) use ($mapping) {
                    return $mapping[$m[0]];
                }, $dates->to);

                try {
                    $from = \Carbon\Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:s');
                    $to = \Carbon\Carbon::parse($to)->endOfDay()->format('Y-m-d H:i:s');

                    $this->crud->query = $this->crud->query->whereBetween('created_at', [$from, $to]);
                } catch (\Exception $e) {
                    \Log::warning('Invalid date range filter (Booking): '.$value);
                    // silently ignore bad dates – same behaviour as TransactionController
                }
            }
        );
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
        return $this->setupListOperation();
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

    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false); // تعطيل التوليد التلقائي من قاعدة البيانات

        // جدول معلومات العميل والشقة
        CRUD::addColumn([
            'name' => 'معلومات&nbsp; العميل',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>'.__('cms.customer_info').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>'.__('cms.customer').' <i class="la la-user"></i></th>
                            <td>'.e($entry->customer_full_name).'</td>
                        </tr>
                        <tr>
                            <th>'.__('cms.email').' <i class="la la-envelope"></i></th>
                            <td>'.optional($entry->customer)->email.'</td>
                        </tr>
                        <tr>
                            <th>'.__('cms.phone').' <i class="la la-phone"></i></th>
                            <td dir="ltr">'.optional($entry->customer)->phone.'</td>
                        </tr>
                        <tr>
                            <th>'.__('cms.apartment').' <i class="la la-building"></i></th>
                            <td>'.optional($entry->apartment)->name_ar.'</td>
                        </tr>
                    </table>';
            },
        ]);

        // جدول التواريخ وعدد الليالي
        CRUD::addColumn([
            'name' => '   تفاصيل&nbsp;  الحجز',
            'type' => 'custom_html',
            'value' => function ($entry) {
                // تحديد الأيقونة واللون حسب مصدر الحجز
                $sourceIcons = [
                    'web' => '<i class="la la-globe text-primary"></i>',
                    'android' => '<i class="la la-android text-success"></i>',
                    'ios' => '<i class="la la-apple text-dark"></i>',
                    'ownerrez' => '<i class="la la-link text-info"></i>',
                    'airbnb' => '<i class="la la-home text-danger"></i>',
                    'booking_com' => '<i class="la la-bed text-primary"></i>',
                    'guesty' => '<i class="la la-building text-warning"></i>',
                    'other' => '<i class="la la-question-circle text-secondary"></i>',
                ];

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

                $bookingSource = $entry->booking_source ?? 'web';
                $sourceIcon = $sourceIcons[$bookingSource] ?? $sourceIcons['other'];
                $sourceLabel = $sourceLabels[$bookingSource] ?? ucfirst($bookingSource);

                // إضافة معلومات إضافية لحجوزات OwnerRez
                $ownerrezInfo = '';
                if ($entry->ownerrez_booking_id) {
                    $ownerrezInfo .= '
                        <tr>
                            <th>رقم حجز OwnerRez <i class="la la-link"></i></th>
                            <td><span class="badge badge-secondary">'.$entry->ownerrez_booking_id.'</span></td>
                        </tr>';
                }
                if ($entry->channel_name) {
                    $ownerrezInfo .= '
                        <tr>
                            <th>اسم القناة <i class="la la-tag"></i></th>
                            <td><span class="badge badge-warning">'.$entry->channel_name.'</span></td>
                        </tr>';
                }
                if ($entry->external_reference) {
                    $ownerrezInfo .= '
                        <tr>
                            <th>المرجع الخارجي <i class="la la-code"></i></th>
                            <td><span class="badge badge-light">'.$entry->external_reference.'</span></td>
                        </tr>';
                }

                return '
                    <h5><strong>'.__('cms.booking_details').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>رقم الحجز <i class="la la-bookmark"></i></th>
                            <td><span class="badge badge-dark">'.$entry->number_of_booking.'</span></td>
                        </tr>
                        <tr>
                            <th>مصدر الحجز '.$sourceIcon.'</th>
                            <td><span class="badge badge-info">'.$sourceLabel.'</span></td>
                        </tr>
                        '.$ownerrezInfo.'
                        <tr>
                            <th>'.__('cms.check_in').' <i class="la la-calendar-check"></i></th>
                            <td><span class="badge badge-success">'.\Carbon\Carbon::parse($entry->check_in)->format('d F Y').'</span></td>
                        </tr>
                        <tr>
                            <th>'.__('cms.check_out').' <i class="la la-calendar-times"></i></th>
                            <td><span class="badge badge-danger">'.\Carbon\Carbon::parse($entry->check_out)->format('d F Y').'</span></td>
                        </tr>
                        <tr>
                            <th>'.__('cms.number_of_nights').' <i class="la la-moon"></i></th>
                            <td><span class="badge badge-info">'.$entry->number_of_nights.'</span></td>
                        </tr>
                        <tr>
                            <th>'.__('cms.booking_date').' <i class="la la-calendar-plus"></i></th>
                            <td><span class="badge badge-primary">'.\Carbon\Carbon::parse($entry->created_at)->format('d F Y H:i').'</span></td>
                        </tr>
                    </table>';
            },
        ]);

        // جدول المعلومات المالية
        CRUD::addColumn([
            'name' => 'المعلومات &nbsp; المالية',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>'.__('cms.financial_info').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th> المبلغ الإجمالي قبل الضريبة (SAR) <i class="la la-money"></i></th>
                            <td><span class="font-weight-bold text-primary">'.number_format($entry->total_price_before_tax, 2).' SAR</span></td>
                        </tr>
                        <tr>
                            <th> الضريبة (SAR) <i class="la la-money"></i></th>
                            <td><span class="font-weight-bold text-primary">'.number_format($entry->tax, 2).' SAR</span></td>
                        </tr>
                        <tr>
                            <th> المبلغ الإجمالي شامل الضريبة (SAR) <i class="la la-money"></i></th>
                            <td><span class="font-weight-bold text-primary">'.number_format($entry->total_price, 2).' SAR</span></td>
                        </tr>
                        '.($entry->discount ? '<tr>
                            <th>'.__('cms.discount').' (SAR)</th>
                            <td><span class="font-weight-bold text-danger">'.number_format($entry->discount, 2).' SAR</span></td>
                        </tr>
                        <tr>
                            <th>نسبة الخصم (%)</th>
                            <td><span class="font-weight-bold text-danger">'.(($entry->total_price > 0) ? number_format(($entry->discount / $entry->total_price) * 100) : '0').'%</span></td>
                        </tr>' : '').'
                        '.($entry->coupon ? '<tr>
                            <th>'.__('cms.coupon').' <i class="la la-tag"></i></th>
                            <td>'.$entry->coupon->code.'</td>
                        </tr>' : '').'
                        <tr>
                            <th> المبلغ النهائي شامل الضريبة (SAR) <i class="la la-money-bill"></i></th>
                            <td><span class="font-weight-bold text-success">'.number_format($entry->final_price, 2).' SAR</span></td>
                        </tr>
                    </table>';
            },
        ]);

        // CRUD::addColumn([
        //     'name' =>   'معلومات &nbsp; الحالة',
        //     'type' => 'custom_html',
        //     'value' => function ($entry) {
        //         return '
        //             <h5><strong>' . __('cms.status_info') . '</strong></h5>
        //             <table class="table table-bordered">
        //                 <tr>
        //                     <th>' . __('cms.status') . '</th>
        //                     <td>' . $this->getStatusBadge($entry->status) . '</td>
        //                 </tr>
        //                 <tr>
        //                     <th>' . __('cms.payment_status') . '</th>
        //                     <td>' . $this->getPaymentStatusBadge($entry->payment_status) . '</td>
        //                 </tr>
        //             </table>';
        //     }
        // ]);

        // جدول معلومات الحالة (حالة الحجز + الدفع + الاسترداد)
        CRUD::addColumn([
            'name' => 'معلومات &nbsp; الحالة',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $refundRow = $entry->refund_status ? '
                        <tr>
                            <th>'.__('cms.refund_status').' <i class="la la-money-bill-wave"></i></th>
                            <td>'.$this->getRefundStatusBadge($entry->refund_status).
                            ($entry->refund_amount ? ' <span class="text-muted">('.number_format($entry->refund_amount, 2).' SAR)</span>' : '').'</td>
                        </tr>' : '';

                return '
                    <h5><strong>'.__('cms.status_info').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>'.__('cms.status').' <i class="la la-info-circle"></i></th>
                            <td>'.$this->getStatusBadge($entry->status).'</td>
                        </tr>
                        <tr>
                            <th>'.__('cms.payment_status').' <i class="la la-credit-card"></i></th>
                            <td>'.e($entry->payment_status).'</td>
                        </tr>'.$refundRow.'
                    </table>';
            },
        ]);

        // جدول لعدد البالغين والأطفال وطريقة الدفع
        CRUD::addColumn([
            'name' => 'معلومات &nbsp;&nbsp;إضافية',

            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>'.__('cms.additional_info').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>'.__('cms.adults_count').' <i class="la la-user"></i></th>
                            <td>'.$entry->adults_count.'</td>
                        </tr>
                        <tr>
                            <th>'.__('cms.children_count').' <i class="la la-child"></i></th>
                            <td>'.$entry->children_count.'</td>
                        </tr>
                        <tr>
                            <th>'.__('cms.payment_method_code').' <i class="la la-wallet"></i></th>
                            <td>'.$entry->payment_method_code.'</td>
                        </tr>
                    </table>';
            },
        ]);
    }

    // دالة مساعدة لتنسيق حالة الاسترداد كـBadge
    protected function getRefundStatusBadge($refundStatus)
    {
        $labels = [
            'pending' => __('cms.refund_status_pending'),
            'processing' => __('cms.refund_status_processing'),
            'approved' => __('cms.refund_status_approved'),
            'rejected' => __('cms.refund_status_rejected'),
            'failed' => __('cms.refund_status_failed'),
        ];
        $colors = [
            'pending' => '#f0ad4e',
            'processing' => '#3498db',
            'approved' => '#28a745',
            'rejected' => '#b02a37',
            'failed' => '#e74c3c',
        ];
        $icons = [
            'pending' => 'la-clock',
            'processing' => 'la-spinner',
            'approved' => 'la-check-circle',
            'rejected' => 'la-times-circle',
            'failed' => 'la-exclamation-triangle',
        ];
        $color = $colors[$refundStatus] ?? '#6c757d';
        $icon = $icons[$refundStatus] ?? 'la-money-bill-wave';
        $label = $labels[$refundStatus] ?? ucfirst((string) $refundStatus);

        return "<span class='badge' style='background-color:{$color};color:#fff;padding:.45em .7em;font-size:.82rem;font-weight:600;border-radius:6px;'><i class='la {$icon}' style='font-size:1.05rem;vertical-align:-2px;'></i> {$label}</span>";
    }

    // دالة مساعدة لتنسيق الحالة كـBadge
    protected function getStatusBadge($status)
    {
        $statusLabels = [
            'pending' => __('cms.status_pending'),
            'approved' => __('cms.status_approved'),
            'rejected' => __('cms.status_rejected'),
            'booked' => __('cms.status_booked'),
            'finished' => __('cms.status_finished'),
            'canceled' => __('cms.status_canceled'),
            'customer_canceled' => __('cms.status_customer_canceled'),
        ];
        $statusColors = [
            'pending' => '#6c757d',
            'approved' => '#28a745',
            'rejected' => '#b02a37',
            'booked' => '#007bff',
            'finished' => '#343a40',
            'canceled' => '#dc3545',
            'customer_canceled' => '#fd7e14',
        ];
        $statusIcons = [
            'pending' => 'la-clock',
            'approved' => 'la-check-circle',
            'rejected' => 'la-times-circle',
            'booked' => 'la-calendar-check',
            'finished' => 'la-flag-checkered',
            'canceled' => 'la-ban',
            'customer_canceled' => 'la-user-times',
        ];
        $color = $statusColors[$status] ?? '#17a2b8';
        $icon = $statusIcons[$status] ?? 'la-info-circle';
        $label = $statusLabels[$status] ?? ucfirst($status);

        return "<span class='badge' style='background-color:{$color};color:#fff;padding:.45em .7em;font-size:.82rem;font-weight:600;border-radius:6px;'><i class='la {$icon}' style='font-size:1.05rem;vertical-align:-2px;'></i> {$label}</span>";
    }

    // دالة مساعدة لتنسيق حالة الدفع كـBadge
    protected function getPaymentStatusBadge($paymentStatus)
    {
        $paymentStatusLabels = [
            'pending' => __('cms.payment_status_pending'),
            'paid' => __('cms.payment_status_paid'),
            'failed' => __('cms.payment_status_failed'),
        ];
        $paymentStatusColors = [
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
        ];
        $color = $paymentStatusColors[$paymentStatus] ?? 'info';
        $label = $paymentStatusLabels[$paymentStatus] ?? ucfirst($paymentStatus);

        return "<span class='badge badge-{$color}'>{$label}</span>";
    }

    public function changeStatus($id, $status)
    {
        $booking = \App\Models\Booking::find($id);
        if ($booking) {
            $booking->status = $status;
            $booking->save();

            $booking = \App\Models\Booking::find($id);
            if (($status == 'canceled' || $status == 'customer_canceled') && $booking->passcode_status == 'generated') {

                $bookingActivePasscode = $booking->smartLockPasscodes()->first();

                \Log::info($bookingActivePasscode);
                if ($bookingActivePasscode) {

                    DB::beginTransaction();
                    try {
                        $lock_id = $bookingActivePasscode->smart_lock_id;
                        $passcode_id = $bookingActivePasscode->passcode_id;
                        $bookingActivePasscode->delete();

                        $scienerService = new ScienerLockService(
                            $booking?->apartment?->building?->ttlock_username,
                            $booking?->apartment?->building?->ttlock_password
                        );
                        $scienerService->deletePasscode($lock_id, $passcode_id);
                        DB::commit();
                    } catch (\Throwable $th) {
                        \Log::error($th);
                        \Alert::error(__('cms.failed_to_delete_passcode'))->flash();
                        DB::rollBack();
                    }

                }
            }
            \Alert::success(__('cms.status_changed_successfully'))->flash();
        } else {
            \Alert::error(__('cms.booking_not_found'))->flash();
        }

        return back();
    }

    public function changePaymentStatus($id, $status)
    {
        $booking = \App\Models\Booking::find($id);
        if ($booking) {
            $booking->payment_status = $status;
            $booking->save();
            \Alert::success(__('cms.payment_status_changed_successfully'))->flash();
        } else {
            \Alert::error(__('cms.booking_not_found'))->flash();
        }

        return back();
    }

    protected function addBuildingFilter()
    {
        CRUD::addFilter([
            'name' => 'building_id',
            'type' => 'dropdown',
            'label' => __('cms.building'),
        ], function () {
            return \App\Models\Building::all()->pluck('name_ar', 'id')->toArray();
        }, function ($value) {
            CRUD::addClause('whereHas', 'apartment', function ($query) use ($value) {
                $query->where('building_id', $value);
            });
        });
    }

    protected function addStatusFilter()
    {
        CRUD::addFilter([
            'name' => 'status',
            'type' => 'dropdown',
            'label' => __('cms.status'),
        ], [
            'pending' => __('cms.status_pending'),
            'approved' => __('cms.status_approved'),
            'rejected' => __('cms.status_rejected'),
            'booked' => __('cms.status_booked'),
            'finished' => __('cms.status_finished'),
            'canceled' => __('cms.status_canceled'),
            'customer_canceled' => __('cms.status_customer_canceled'),
        ], function ($value) {
            CRUD::addClause('where', 'status', $value);
        });
    }

    protected function addPaymentStatusFilter()
    {
        CRUD::addFilter([
            'name' => 'payment_status',
            'type' => 'dropdown',
            'label' => __('cms.payment_status'),
        ], [
            'pending' => __('cms.payment_status_pending'),
            'paid' => __('cms.payment_status_paid'),
            'failed' => __('cms.payment_status_failed'),
        ], function ($value) {
            CRUD::addClause('where', 'payment_status', $value);
        });
    }

    protected function addBookingSourceFilter()
    {
        CRUD::addFilter([
            'name' => 'booking_source',
            'type' => 'dropdown',
            'label' => 'مصدر الحجز',
        ], [
            'web' => 'الموقع الإلكتروني',
            'android' => 'أندرويد',
            'ios' => 'آيفون',
            'ownerrez' => 'OwnerRez',
            'airbnb' => 'Airbnb',
            'booking_com' => 'Booking.com',
            'guesty' => 'Guesty',
            'other' => 'أخرى',
        ], function ($value) {
            CRUD::addClause('where', 'booking_source', $value);
        });
    }

    /**
     * عرض صفحة تعديل وقت الدخول
     */
    public function editCheckInTime($id)
    {
        $booking = \App\Models\Booking::with(['apartment', 'customer'])->findOrFail($id);

        // التحقق من الصلاحيات
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        return view('admin.booking.edit-check-in-time', compact('booking'));
    }

    /**
     * تحديث وقت الدخول
     */
    public function updateCheckInTime($id)
    {
        $request = request();

        $booking = \App\Models\Booking::findOrFail($id);

        // التحقق من الصلاحيات
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        // التحقق من صحة البيانات
        $request->validate([
            'check_in_time' => 'required|date_format:H:i',
        ]);

        // دمج تاريخ الوصول مع الوقت الجديد
        $checkInDate = \Carbon\Carbon::parse($booking->check_in)->format('Y-m-d');
        $newTime = $request->check_in_time;
        $newDateTime = $checkInDate.' '.$newTime;

        // التحقق من صحة التاريخ والوقت
        try {
            $parsedDateTime = \Carbon\Carbon::parse($newDateTime);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في تنسيق التاريخ والوقت');
        }

        // تحديث وقت الدخول
        $booking->update([
            'check_in_time' => $parsedDateTime,
        ]);

        // إنشاء passcode جديد للغرفة
        $this->generateNewPasscode($booking);

        return redirect()->back()->with('success', 'تم تحديث وقت الدخول وإنشاء رمز جديد للغرفة بنجاح');
    }

    /**
     * إنشاء passcode جديد للغرفة
     */
    private function generateNewPasscode($booking)
    {
        try {
            // حذف الـ passcodes القديمة
            $booking->smartLockPasscodes()->delete();

            // استخدام BookingService لإنشاء passcode جديد
            $bookingService = app(\App\Services\BookingService::class);
            $bookingService->addPasscodeToSmartLock($booking);

            \Log::info("New passcode generated for booking {$booking->id} using BookingService");

        } catch (\Exception $e) {
            \Log::error("Failed to generate new passcode for booking {$booking->id}: ".$e->getMessage());
        }
    }

    /**
     * إعادة إنشاء الباس كود
     */
    public function regeneratePasscode($id)
    {
        $booking = \App\Models\Booking::findOrFail($id);

        // التحقق من الصلاحيات
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        try {
            // إعادة تعيين حالة الباس كود
            $booking->markPasscodeAsPending();

            // إنشاء باس كود جديد
            $this->generateNewPasscode($booking);

            return redirect()->back()->with('success', 'تم إعادة إنشاء الباس كود بنجاح');

        } catch (\Exception $e) {
            \Log::error("Failed to regenerate passcode for booking {$booking->id}: ".$e->getMessage());

            return redirect()->back()->with('error', 'فشل في إعادة إنشاء الباس كود: '.$e->getMessage());
        }
    }
}
