<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\CancelSource;
use App\Services\Bookings\BookingCancellationService;
use App\Services\Locks\LockAccessService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

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
            // حجوزات Airbnb المستوردة سجلات وهمية تُحذف وتُعاد تلقائياً مع كل مزامنة —
            // تبقى بلا زر تعديل حتى لمن يملك الصلاحية، ويُعرض لها زر "عرض" فقط.
            $this->crud->set('update.access', function ($entry) {
                return ! $entry || ! $entry->is_airbnb_booking;
            });
        }
        // معطّل مؤقتاً لكل المستخدمين (بمن فيهم من يملك صلاحية booking.delete):
        // حذف الحجز نهائياً لا يُلغي كود الدخول على القفل الذكي ولا يحذف سجل Transaction المرتبط،
        // ما يترك كوداً فعّالاً على القفل الحقيقي بلا حجز يدل عليه. أعد التفعيل فقط بعد معالجة ذلك.
        // كما لا يظهر أبداً لحجوزات Airbnb المستوردة حتى لو أُعيد تفعيله لاحقاً.
        // if (backpack_user()->can('booking.delete')) {
        //     $this->crud->set('delete.access', function ($entry) {
        //         return ! $entry || ! $entry->is_airbnb_booking;
        //     });
        // }
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

        // لا نحفظ حالة الجدول (الفلاتر/البحث/الصفحة) بين الزيارات، حتى لا تُفتح صفحة
        // الحجوزات على فلتر "الإلغاءات بحاجة إجراء" تلقائياً — يبقى تطبيقه يدوياً بالضغط عليه.
        $this->crud->setOperationSetting('persistentTable', false);

        // إخفاء حجوزات Airbnb افتراضياً (بما في ذلك البحث)؛ تظهر فقط عند تفعيل
        // فلتر "حجوزات Airbnb" أدناه — راجع addAirbnbFilter().
        if (! request()->boolean('show_airbnb')) {
            $this->crud->query->where('is_airbnb_booking', '!=', 1);
        }

        // زر "حجز مباشر" أعلى الجدول (تحويل بنكي) — يظهر لمن يملك الصلاحية فقط
        if (backpack_user()->can('direct-booking.create')) {
            CRUD::addButtonFromView('top', 'direct_booking', 'direct_booking', 'end');
        }

        Widget::add([
            'type' => 'view',
            'view' => 'admin.booking.copy_passcode_script',
        ])->to('after_content');

        // يمنع اقتصاص قائمة "تغيير الحالة" بجعلها position:fixed عند الفتح (دون لمس overflow الجدول)
        Widget::add([
            'type' => 'view',
            'view' => 'admin.booking.fix_status_dropdown_clip',
        ])->to('after_content');

        // تمييز فلتر "الإلغاءات بحاجة إجراء" بلون واضح (بدل الأبيض) في شريط الفلاتر
        Widget::add([
            'type' => 'view',
            'view' => 'admin.booking.cancellation_filter_style',
        ])->to('after_content');

        $this->addBuildingFilter();
        $this->addStatusFilter();
        $this->addPaymentStatusFilter();
        $this->addBookingSourceFilter();
        $this->addCancellationActionFilter();
        $this->addAirbnbFilter();

        if (backpack_user()->can('booking.changeStatus')) {
            CRUD::addButtonFromModelFunction('line', 'changeStatus', 'getChangeStatusButton', 'end');

            // زر "إدارة الإلغاء" — يظهر فقط للحجوزات التي تحتاج إجراء إلغاء/استرداد،
            // ويفتح نافذة موجّهة (إلغاء عبر OwnerRez / إلغاء محلي / رفض / استرداد).
            CRUD::addButtonFromView('line', 'manage_cancellation', 'manage_cancellation', 'end');

            $this->addCancellationWidgets();

            // نافذة "تأكيد الحجز" (تحقّق Geidea أولاً وإلا تحويل بنكي)
            Widget::add([
                'type' => 'view',
                'view' => 'admin.booking.confirm_modal',
            ])->to('before_content');

            // نافذة تأكيد "الإلغاء" (بدل confirm() الأصلية)
            Widget::add([
                'type' => 'view',
                'view' => 'admin.booking.cancel_modal',
            ])->to('before_content');
        }
        // if (backpack_user()->can('booking.changePaymentStatus')) {
        //     CRUD::addButtonFromModelFunction('line', 'changePaymentStatus', 'getChangePaymentStatusButton', 'end');
        // }

        // إضافة زر تعديل وقت الدخول باستخدام inline button
        if (backpack_user()->can('booking.changeStatus')) {
            CRUD::addButtonFromView('line', 'edit_check_in_time', 'edit_check_in_time', 'end');
        }

        // زر "نقل الوحدة" — بلا صلاحية خاصة: متاح لكل من يستطيع عرض الحجوزات. يظهر فقط
        // للحجوزات المؤهلة (مؤكدة/مدفوعة/قبل الموعد بلا طلب مفتوح) عبر Booking::canBeTransferred().
        CRUD::addButtonFromView('line', 'transfer_unit', 'transfer_unit', 'end');

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
                return $this->getStatusBadge($entry->status, $entry);
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
                    'dashboard' => '<i class="la la-plus-circle"></i>',
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
                    'dashboard' => 'حجز مباشر',
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
                    'dashboard' => 'success',
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
        //         return '<span class="text-primary font-weight-bold">' . number_format($entry->total_price, 2) . ' '.\App\Support\Riyal::svg().'</span>';
        //     }
        // ]);

        // Final Price
        CRUD::addColumn([
            'name' => 'final_price',
            'type' => 'custom_html',
            'label' => 'المبلغ النهائية شامل الضريبة'.' (SAR) <i class="la la-money-bill"></i>',
            'value' => function ($entry) {
                return '<span class="text-success font-weight-bold">'.number_format($entry->final_price, 2).' '.\App\Support\Riyal::svg().'</span>';
            },
        ]);

        // Adults count

        // Payment Method
        CRUD::addColumn([
            'name' => 'payment_method_code',
            'type' => 'custom_html',
            'label' => __('cms.payment_method_code').' <i class="la la-wallet"></i>',
            'value' => function ($entry) {
                $labels = [
                    'tap' => 'Tap',
                    'tabby' => 'Tabby',
                    'geidea' => 'جيديا',
                    'airbnb' => 'Airbnb',
                    'bank_transfer' => 'تحويل بنكي',
                ];
                $code = $entry->payment_method_code;

                return $code ? ($labels[$code] ?? $code) : '<span class="text-muted">—</span>';
            },
        ]);

        // Passcode + copy button
        CRUD::addColumn([
            'name' => 'passcode',
            'type' => 'custom_html',
            'label' => __('cms.passcode').' <i class="la la-key"></i>',
            'value' => function ($entry) {
                // The code is revealed only during the stay (check-in → check-out).
                $active = $entry->getActivePasscode();

                if ($active) {
                    $code = e($active->keyboard_pwd);

                    return "<span class='badge badge-info' style='font-size:.85rem;letter-spacing:1px;'>{$code}</span> "
                        ."<button type='button' class='btn btn-link btn-sm p-0 ms-1' style='vertical-align:baseline;' "
                        ."onclick=\"copyPasscodeToClipboard('{$code}', this)\" title='".__('cms.copy_passcode')."'>"
                        .'<i class="la la-copy"></i></button>';
                }

                // Passcode generation failed (e.g. smart-lock vendor rejected the request) —
                // surface it clearly with a one-click regenerate. A failed provision ends up
                // as 'retry_scheduled' (an auto-retry is queued), so treat both as "failed".
                if (in_array($entry->passcode_status, ['failed', 'retry_scheduled'], true)) {
                    return $this->passcodeFailedCell($entry);
                }

                // Code generated but the stay hasn't started yet — keep it hidden, but reassure
                // staff it's ready and will appear automatically at check-in.
                $upcoming = $entry->smartLockPasscodes()
                    ->where('start_date', '>', now())
                    ->exists();

                if ($upcoming) {
                    return "<span class='badge badge-success' title='".e(__('cms.passcode_ready_tooltip'))."' "
                        ."style='cursor:help;'><i class='la la-lock'></i> ".__('cms.passcode_ready').'</span>';
                }

                return '<span class="text-muted">—</span>';
            },
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

        // نفس دالة نسخ الكود المستخدمة في الجدول
        Widget::add([
            'type' => 'view',
            'view' => 'admin.booking.copy_passcode_script',
        ])->to('after_content');

        // لوحة "نقل الوحدة" في صفحة التفاصيل: حالة الطلب، إلغاء طلب معلّق، رابط إلغاء حجز
        // OwnerRez القديم يدوياً، وزر استرداد فرق الوحدة الأرخص. تظهر لكل من يعرض الحجوزات.
        $transferBooking = $this->crud->getCurrentEntry();
        if ($transferBooking) {
            $transfers = $transferBooking->unitTransfers()
                ->with(['fromApartment', 'toApartment', 'initiatedBy'])
                ->latest()
                ->get();
            if ($transfers->isNotEmpty()) {
                Widget::add([
                    'type' => 'view',
                    'view' => 'admin.booking.unit_transfer_panel',
                    'booking' => $transferBooking,
                    'transfer' => $transfers->first(),
                    'transfers' => $transfers,
                ])->to('before_content');
            }
        }

        // زر "إدارة الإلغاء" + النوافذ في صفحة التفاصيل (لمن يملك صلاحية تغيير الحالة)
        if (backpack_user()->can('booking.changeStatus')) {
            $currentBooking = $this->crud->getCurrentEntry();
            if ($currentBooking) {
                Widget::add([
                    'type' => 'view',
                    'view' => 'admin.booking.cancellation_show_action',
                    'booking' => $currentBooking,
                    'ownerrezCanceled' => $this->ownerrezBookingIsCanceled($currentBooking),
                ])->to('before_content');
            }
            $this->addCancellationWidgets();
        }

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

        // كود الدخول + زر النسخ (نفس سلوك الجدول)
        CRUD::addColumn([
            'name' => 'كود&nbsp;الدخول',
            'type' => 'custom_html',
            'value' => function ($entry) {
                // Mirror the list column: reveal the code only during the stay; before it
                // starts, show only the "ready — appears at check-in" badge.
                $active = $entry->getActivePasscode();

                if ($active) {
                    $code = e($active->keyboard_pwd);
                    $cell = "<span class='badge badge-info' style='font-size:.95rem;letter-spacing:1px;'>{$code}</span> "
                        ."<button type='button' class='btn btn-link btn-sm p-0 ms-1' style='vertical-align:baseline;' "
                        ."onclick=\"copyPasscodeToClipboard('{$code}', this)\" title='".__('cms.copy_passcode')."'>"
                        .'<i class="la la-copy"></i></button>';
                } elseif (in_array($entry->passcode_status, ['failed', 'retry_scheduled'], true)) {
                    $cell = $this->passcodeFailedCell($entry, true);
                } else {
                    $upcoming = $entry->smartLockPasscodes()
                        ->where('start_date', '>', now())
                        ->exists();

                    if (! $upcoming) {
                        return '';
                    }

                    $cell = "<span class='badge badge-success' title='".e(__('cms.passcode_ready_tooltip'))."' "
                        ."style='cursor:help;'><i class='la la-lock'></i> ".__('cms.passcode_ready').'</span>';
                }

                return '
                    <h5><strong>'.__('cms.passcode').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>'.__('cms.passcode').' <i class="la la-key"></i></th>
                            <td>'.$cell.'</td>
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
                    'dashboard' => '<i class="la la-plus-circle text-success"></i>',
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
                    'dashboard' => 'حجز مباشر (لوحة التحكم)',
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
                            <td><span class="font-weight-bold text-primary">'.number_format($entry->total_price_before_tax, 2).' '.\App\Support\Riyal::svg().'</span></td>
                        </tr>
                        <tr>
                            <th> الضريبة (SAR) <i class="la la-money"></i></th>
                            <td><span class="font-weight-bold text-primary">'.number_format($entry->tax, 2).' '.\App\Support\Riyal::svg().'</span></td>
                        </tr>
                        <tr>
                            <th> المبلغ الإجمالي شامل الضريبة (SAR) <i class="la la-money"></i></th>
                            <td><span class="font-weight-bold text-primary">'.number_format($entry->total_price, 2).' '.\App\Support\Riyal::svg().'</span></td>
                        </tr>
                        '.($entry->discount ? '<tr>
                            <th>'.__('cms.discount').' (SAR)</th>
                            <td><span class="font-weight-bold text-danger">'.number_format($entry->discount, 2).' '.\App\Support\Riyal::svg().'</span></td>
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
                            <td><span class="font-weight-bold text-success">'.number_format($entry->final_price, 2).' '.\App\Support\Riyal::svg().'</span></td>
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
                            ($entry->refund_amount ? ' <span class="text-muted">('.number_format($entry->refund_amount, 2).' '.\App\Support\Riyal::svg().')</span>' : '').'</td>
                        </tr>' : '';

                return '
                    <h5><strong>'.__('cms.status_info').'</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>'.__('cms.status').' <i class="la la-info-circle"></i></th>
                            <td>'.$this->getStatusBadge($entry->status, $entry).'</td>
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
                            <td>'.($entry->payment_method_code === 'bank_transfer' ? 'تحويل بنكي' : ($entry->payment_method_code ?: '—')).'</td>
                        </tr>
                    </table>';
            },
        ]);

        // بيانات التحويل البنكي (للحجوزات المباشرة من لوحة التحكم)
        CRUD::addColumn([
            'name' => 'بيانات&nbsp;التحويل',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $tx = $entry->transaction;
                $receiptUrl = $tx ? $tx->receiptUrl() : '';
                $transferNumber = $tx->transfer_number ?? null;

                // Only render for manual/bank-transfer bookings that actually carry transfer data.
                $isBankTransfer = ($entry->payment_method_code === 'bank_transfer')
                    || ($tx && $tx->payment_gateway === 'bank_transfer');
                if (! $isBankTransfer && ! $transferNumber && ! $receiptUrl) {
                    return '';
                }

                $numberRow = '
                    <tr>
                        <th>'.__('cms.transfer_number').' <i class="la la-hashtag"></i></th>
                        <td dir="ltr">'.($transferNumber ? e($transferNumber) : '<span class="text-muted">—</span>').'</td>
                    </tr>';

                $receiptRow = '
                    <tr>
                        <th>'.__('cms.transfer_receipt').' <i class="la la-image"></i></th>
                        <td>'.($receiptUrl
                            ? '<a href="'.e($receiptUrl).'" target="_blank" rel="noopener"><img src="'.e($receiptUrl).'" alt="receipt" style="max-height:120px;max-width:100%;border:1px solid #eee;border-radius:6px;"></a>'
                            : '<span class="text-muted">—</span>').'</td>
                    </tr>';

                return '
                    <h5><strong>'.__('cms.bank_transfer_details').'</strong></h5>
                    <table class="table table-bordered">'.$numberRow.$receiptRow.'</table>';
            },
        ]);
    }

    /**
     * "Passcode generation failed" cell + a one-click regenerate button (shown only to
     * users allowed to manage the lock). The regenerate route already catches failures
     * and flashes an error, so a still-broken lock won't 500 either.
     */
    private function passcodeFailedCell($entry, bool $detailed = false): string
    {
        $error = trim((string) ($entry->passcode_error ?? ''));

        $attempt = \App\Models\PasscodeRetryAttempt::where('booking_id', $entry->getKey())
            ->where('operation', 'provision')
            ->latest('id')
            ->first();

        $maxReached = $attempt && $attempt->status === 'max_attempts_reached';

        // One-click regenerate (only for users allowed to manage the lock). The route
        // catches failures and flashes a detailed error, so a still-broken lock won't 500.
        $regen = '';
        if (backpack_user()->can('booking.changeStatus')) {
            $url = url(config('backpack.base.route_prefix').'/booking/'.$entry->getKey().'/regenerate-passcode');
            $regen = "<form method='POST' action='{$url}' style='display:inline;' "
                ."onsubmit=\"return confirm('".e(__('cms.regenerate_passcode_confirm'))."')\">".csrf_field()
                ."<button type='submit' class='btn btn-xs btn-warning' title='".e(__('cms.regenerate_passcode'))."'>"
                ."<i class='la la-redo'></i> ".__('cms.regenerate_passcode').'</button></form>';
        }

        // Compact cell for the list: badge (full reason in tooltip) + short retry line + button.
        if (! $detailed) {
            $tooltip = $error !== '' ? $error : __('cms.passcode_failed');
            $badge = "<span class='badge' title='".e($tooltip)."' "
                ."style='background-color:#e74c3c;color:#fff;padding:.35em .55em;border-radius:6px;cursor:help;'>"
                ."<i class='la la-exclamation-triangle'></i> ".__('cms.passcode_failed').'</span>';

            $note = '';
            if ($maxReached) {
                $note = "<div style='font-size:.72rem;color:#c0392b;margin-top:2px;'>".__('cms.passcode_permanent_error').'</div>';
            } elseif ($attempt && $attempt->next_attempt_at) {
                $note = "<div style='font-size:.72rem;color:#7f8c8d;margin-top:2px;'>"
                    .__('cms.passcode_attempts').': '.((int) $attempt->attempt_count).'/'.((int) $attempt->max_attempts)
                    .' — '.__('cms.passcode_next_retry').' '.e($attempt->next_attempt_at->format('Y-m-d H:i')).'</div>';
            }

            return "<div>{$badge} {$regen}{$note}</div>";
        }

        // Detailed cell for the show page: full reason + attempt breakdown + button.
        $badge = "<span class='badge' style='background-color:#e74c3c;color:#fff;padding:.35em .55em;border-radius:6px;'>"
            ."<i class='la la-exclamation-triangle'></i> ".__('cms.passcode_failed').'</span>';

        $rows = '';
        if ($error !== '') {
            $rows .= "<tr><th style='width:190px;'>".__('cms.passcode_error_label').'</th>'
                ."<td style='color:#c0392b;'>".e($error).'</td></tr>';
        }
        if ($attempt) {
            $rows .= '<tr><th>'.__('cms.passcode_attempts').'</th><td>'
                .((int) $attempt->attempt_count).'/'.((int) $attempt->max_attempts).' — '.e($attempt->status).'</td></tr>';

            if ($maxReached) {
                $rows .= '<tr><th>'.__('cms.status')."</th><td style='color:#c0392b;'>".__('cms.passcode_permanent_error').'</td></tr>';
            } elseif ($attempt->next_attempt_at) {
                $rows .= '<tr><th>'.__('cms.passcode_next_retry').'</th><td>'.e($attempt->next_attempt_at->format('Y-m-d H:i')).'</td></tr>';
            }
            if ($attempt->last_attempt_at) {
                $rows .= '<tr><th>'.__('cms.passcode_last_attempt').'</th><td>'.e($attempt->last_attempt_at->format('Y-m-d H:i')).'</td></tr>';
            }
        }

        $detail = $rows !== ''
            ? "<table class='table table-bordered' style='margin-top:8px;font-size:.85rem;'>{$rows}</table>"
            : '';

        return "<div>{$badge} {$regen}{$detail}</div>";
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

    // دالة مساعدة لتنسيق الحالة كـBadge — تعتمد على BookingStatus (مصدر واحد للحقيقة).
    // عند تمرير الحجز، تُظهر حالة "طلب الإلغاء" وصفاً حسب المصدر (عميل/إدارة).
    protected function getStatusBadge($status, $booking = null)
    {
        $enum = BookingStatus::tryFrom((string) $status);

        if (! $enum) {
            return "<span class='badge' style='background-color:#17a2b8;color:#fff;padding:.45em .7em;font-size:.82rem;font-weight:600;border-radius:6px;'><i class='la la-info-circle' style='font-size:1.05rem;vertical-align:-2px;'></i> ".e(ucfirst((string) $status)).'</span>';
        }

        $label = ($enum === BookingStatus::CancellationRequested && $booking)
            ? $this->cancellationRequestLabel($booking)
            : null;

        return $enum->badge($label);
    }

    /** Source-aware label for the cancellation-request state (customer vs. staff). */
    private function cancellationRequestLabel($booking): string
    {
        return $booking->cancellationStartedByStaff()
            ? __('cms.status_cancellation_requested_staff')
            : __('cms.status_cancellation_requested_customer');
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
        if (! $booking) {
            \Alert::error(__('cms.booking_not_found'))->flash();

            return back();
        }

        // الإلغاء لا يُطبَّق مباشرةً: يمر عبر خدمة الإلغاء الموجّهة. الوحدات المربوطة بـ
        // OwnerRez تبدأ كـ"طلب إلغاء" (تبقى الوحدة محجوزة) ويُنهيها الإلغاء في OwnerRez
        // (عبر الويبهوك) أو "الإلغاء القسري" محلياً؛ غير المربوطة تُلغى محلياً فوراً.
        if ($status === BookingStatus::Canceled->value) {
            $outcome = app(BookingCancellationService::class)->startStaffCancellation($booking);

            match ($outcome) {
                'requested_ownerrez' => \Alert::warning(__('cms.cancel_started_ownerrez'))->flash(),
                'canceled_local' => \Alert::success(__('cms.status_changed_successfully'))->flash(),
                default => \Alert::info(__('cms.booking_already_canceled'))->flash(),
            };

            return back();
        }

        // لم يعد هناك أي تغيير حالة مباشر مسموح عبر هذا المسار: «تأكيد» عبر confirmBooking،
        // و«طلب إلغاء» عبر نافذة إدارة الإلغاء. بقية الحالات («قيد الانتظار»/«محجوز»/
        // «منتهي»/«مرفوض») غير متاحة كإجراء يدوي.
        \Alert::error(__('cms.invalid_booking_status'))->flash();

        return back();
    }

    /**
     * Confirm a PENDING booking: verify Geidea first (auto-confirm a real online payment
     * whose webhook was missed); otherwise record it as a bank transfer (حوالة) with an
     * optional transfer number + receipt image, mark it paid + approved, and run the side
     * effects (lock code, OwnerRez sync, notifications).
     */
    public function confirmBooking($id, \Illuminate\Http\Request $request)
    {
        $this->authorizeLockManagement();

        $booking = \App\Models\Booking::findOrFail($id);

        if ($booking->status !== BookingStatus::Pending->value) {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $validated = $request->validate([
            'transfer_number' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'transfer_number' => __('cms.transfer_number'),
            'receipt' => __('cms.receipt_image'),
        ]);

        try {
            $mode = app(\App\Services\DirectBookingService::class)->confirmExistingBooking(
                $booking,
                $validated['transfer_number'] ?? null,
                $request->file('receipt'),
            );

            \Alert::success($mode === 'geidea'
                ? __('cms.booking_confirmed_geidea')
                : __('cms.booking_confirmed_bank_transfer'))->flash();
        } catch (\Throwable $e) {
            \Log::error("Failed to confirm booking {$booking->id}: ".$e->getMessage());
            \Alert::error(__('cms.booking_confirm_failed').': '.$e->getMessage())->flash();
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

    /**
     * Mount the shared cancellation + refund modals (populated per-row via data-*),
     * used by the "Manage cancellation" / refund buttons on both list and show.
     */
    private function addCancellationWidgets(): void
    {
        Widget::add([
            'type' => 'view',
            'view' => 'admin.booking.cancellation_modals',
        ])->to('before_content');
    }

    /**
     * Live-check OwnerRez: is this booking's reservation actually cancelled (or deleted)
     * there? Gates the local "force cancel" fallback so staff can only free the unit
     * locally once OwnerRez itself no longer holds it (i.e. the webhook was missed).
     * Only calls the API for a mapped booking still awaiting cancellation; any error,
     * unmapped or non-pending booking returns false (hide the action).
     */
    private function ownerrezBookingIsCanceled(\App\Models\Booking $booking): bool
    {
        if (! $booking->isLinkedToOwnerRez() || ! $booking->isCancellationRequested()) {
            return false;
        }

        try {
            $data = app(\App\Services\OwnerRez\OwnerRezApiService::class)
                ->getBooking((int) $booking->ownerrez_booking_id);
        } catch (\App\Exceptions\OwnerRez\OwnerRezApiException $e) {
            // Deleted in OwnerRez (404) counts as cancelled; other errors → cannot confirm.
            return $e->getStatusCode() === 404;
        } catch (\Throwable $e) {
            return false;
        }

        return strtolower((string) ($data['status'] ?? '')) === 'canceled'
            || ! empty($data['canceled_utc']);
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
        ], BookingStatus::options(), function ($value) {
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

    /**
     * On-demand filter (available in the booking list's filters bar, not applied by
     * default and not linked from the sidebar): cancellations that still need staff
     * action — a request awaiting finalization, or a finalized cancel awaiting refund.
     */
    protected function addCancellationActionFilter()
    {
        CRUD::addFilter([
            'name' => 'cancellation_action',
            'type' => 'simple',
            'label' => __('cms.cancellations_needing_action'),
        ], false, function () {
            CRUD::addClause('whereIn', 'status', BookingStatus::cancellationWorkflow());
            CRUD::addClause('where', 'refund_status', 'pending');
        });
    }

    /**
     * On-demand filter: swaps the default "hide Airbnb bookings" query for
     * "show Airbnb bookings only" — the base exclusion in setupListOperation()
     * checks the same 'show_airbnb' request flag this filter toggles.
     */
    protected function addAirbnbFilter()
    {
        CRUD::addFilter([
            'name' => 'show_airbnb',
            'type' => 'simple',
            'label' => 'حجوزات Airbnb فقط',
        ], false, function () {
            CRUD::addClause('where', 'is_airbnb_booking', 1);
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
            'dashboard' => 'حجز مباشر (لوحة التحكم)',
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
        $this->authorizeLockManagement();

        $booking = \App\Models\Booking::with(['apartment', 'customer'])->findOrFail($id);

        return view('admin.booking.edit-check-in-time', compact('booking'));
    }

    /**
     * تحديث وقت الدخول
     */
    public function updateCheckInTime($id)
    {
        $this->authorizeLockManagement();

        $request = request();

        $booking = \App\Models\Booking::findOrFail($id);

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

        // إعادة إنشاء كود الدخول (إلغاء القديم + توليد جديد) عبر الخدمة المركزية
        try {
            app(LockAccessService::class)->rescheduleForBooking($booking);
        } catch (\Throwable $e) {
            \Log::error("Failed to reschedule passcode for booking {$booking->id}: ".$e->getMessage());
        }

        return redirect()->back()->with('success', 'تم تحديث وقت الدخول وإنشاء رمز جديد للغرفة بنجاح');
    }

    /**
     * إعادة إنشاء الباس كود
     */
    public function regeneratePasscode($id)
    {
        $this->authorizeLockManagement();

        $booking = \App\Models\Booking::findOrFail($id);

        try {
            app(LockAccessService::class)->rescheduleForBooking($booking);

            return redirect()->back()->with('success', __('cms.regenerate_passcode_success'));
        } catch (\Throwable $e) {
            \Log::error("Failed to regenerate passcode for booking {$booking->id}: ".$e->getMessage());

            $d = \App\Services\Locks\LockErrorPresenter::describe($e);

            $message = __('cms.regenerate_passcode_failed').': '.$d['summary'];
            if ($d['vendor_code'] !== null) {
                $message .= ' — '.__('cms.passcode_vendor_code').' '.$d['vendor_code'];
                if ($d['vendor_desc']) {
                    $message .= ' ('.$d['vendor_desc'].')';
                }
            }
            $message .= '. '.($d['retryable'] ? __('cms.passcode_will_retry') : __('cms.passcode_permanent_error'));

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * صلاحية موحّدة لكل عمليات إدارة قفل الحجز (وقت الدخول، إعادة إنشاء الكود).
     */
    private function authorizeLockManagement(): void
    {
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }
    }
}
