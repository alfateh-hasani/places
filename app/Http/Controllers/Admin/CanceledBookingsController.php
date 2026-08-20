<?php

namespace App\Http\Controllers\Admin;

use App\Events\CustomerCancellationAccepted;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CanceledBookingsController extends CrudController
{
    use ListOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Booking::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/canceled-bookings');
        CRUD::setEntityNameStrings(__('cms.canceled_bookings'), __('cms.canceled_bookings'));

        if (! backpack_user()->can('booking.list')) {
            abort(403, 'Unauthorized Access');
        }

        // شاشة إجراء: فقط طلبات الإلغاء التي تنتظر قرار الموظف (قبول/رفض).
        // متابعة الاسترداد (processing / failed / refunded) لها شاشة مستقلة: "المستردات".
        CRUD::addClause('where', 'status', 'customer_canceled');
        CRUD::addClause('where', 'refund_status', 'pending');
        CRUD::addClause('orderBy', 'created_at', 'desc');
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
        $this->crud->removeAllButtons();
        $this->crud->removeAllButtonsFromStack('line');

        // إضافة الأعمدة
        CRUD::addColumn([
            'name' => 'number_of_booking',
            'type' => 'text',
            'label' => __('cms.number_of_booking'),
        ]);

        // Booking Source
        CRUD::addColumn([
            'name' => 'booking_source',
            'type' => 'custom_html',
            'label' => 'مصدر الحجز',
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

        CRUD::addColumn([
            'name' => 'customer_id',
            'type' => 'select',
            'label' => __('cms.customer'),
            'entity' => 'customer',
            'attribute' => 'first_name',
            'model' => \App\Models\Customer::class,
        ]);

        CRUD::addColumn([
            'name' => 'apartment_id',
            'type' => 'select',
            'label' => __('cms.apartment'),
            'entity' => 'apartment',
            'attribute' => 'name_ar',
            'model' => \App\Models\Apartment::class,
        ]);

        CRUD::addColumn([
            'name' => 'check_in',
            'type' => 'date',
            'label' => __('cms.check_in'),
        ]);

        CRUD::addColumn([
            'name' => 'check_out',
            'type' => 'date',
            'label' => __('cms.check_out'),
        ]);

        CRUD::addColumn([
            'name' => 'refund_amount',
            'type' => 'custom_html',
            'label' => __('cms.refund_amount'),
            'value' => function ($entry) {
                return '<span class="text-danger font-weight-bold">'.number_format($entry->refund_amount, 2).' SAR</span>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'refund_status',
            'type' => 'custom_html',
            'label' => __('cms.refund_status'),
            'value' => function ($entry) {
                return $this->getRefundStatusBadge($entry->refund_status);
            },
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'datetime',
            'label' => __('cms.canceled_at'),
        ]);

        // إضافة أزرار الإجراءات
        // رابط مباشر لإلغاء الحجز في OwnerRez (للوحدات المربوطة) — الإشعار الوارد يُنهي الإلغاء ويسترد المبلغ تلقائياً
        CRUD::addButtonFromView('line', 'ownerrez_deeplink', 'ownerrez_deeplink', 'end');
        CRUD::addButtonFromView('line', 'approve_refund', 'approve_refund', 'end');
        CRUD::addButtonFromView('line', 'reject_refund', 'reject_refund', 'end');
    }

    /**
     * معالجة طلب الإلغاء/الاسترداد من لوحة الإدارة.
     * - approve: قبول الإلغاء للوحدات غير المربوطة بـ OwnerRez → إنهاء الإلغاء + استرداد تلقائي.
     * - reject:  رفض الطلب وإعادة الحجز نشطاً (آمن؛ الوحدة كانت محجوزة طوال المراجعة).
     * - retry:   إعادة محاولة استرداد فاشل.
     */
    public function processRefund($id, $action)
    {
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        $booking = \App\Models\Booking::findOrFail($id);

        if ($action === 'approve') {
            if ($booking->status !== 'customer_canceled' || $booking->refund_status !== 'pending') {
                \Alert::error(__('cms.invalid_booking_status'))->flash();

                return back();
            }

            // إنهاء الإلغاء (يحرّر الوحدة) ثم إطلاق الاسترداد التلقائي عبر المستمع.
            $booking->status = 'canceled';
            $booking->save();
            event(new CustomerCancellationAccepted($booking));

            \Alert::success(__('cms.refund_approved_successfully'))->flash();
        } elseif ($action === 'reject') {
            if ($booking->status !== 'customer_canceled' || $booking->refund_status !== 'pending') {
                \Alert::error(__('cms.invalid_booking_status'))->flash();

                return back();
            }

            // رفض الطلب وإعادة الحجز نشطاً — آمن ضد الحجز المزدوج لأن الوحدة بقيت محجوزة
            // طوال فترة المراجعة (انظر BookingService::checkAvailability).
            $booking->refund_status = 'rejected';
            $booking->status = 'approved';
            $booking->save();

            \Alert::success(__('cms.refund_rejected_successfully'))->flash();
        } else {
            \Alert::error(__('cms.invalid_action'))->flash();
        }

        return back();
    }

    /**
     * دالة مساعدة لتنسيق حالة الاسترداد كـBadge
     */
    protected function getRefundStatusBadge($refundStatus)
    {
        $refundStatusLabels = [
            'pending' => __('cms.refund_status_pending'),
            'processing' => __('cms.refund_status_processing'),
            'approved' => __('cms.refund_status_approved'),
            'rejected' => __('cms.refund_status_rejected'),
            'failed' => __('cms.refund_status_failed'),
        ];
        $refundStatusColors = [
            'pending' => 'warning',
            'processing' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'failed' => 'danger',
        ];
        $color = $refundStatusColors[$refundStatus] ?? 'info';
        $label = $refundStatusLabels[$refundStatus] ?? ucfirst($refundStatus);

        return "<span class='badge badge-{$color}'>{$label}</span>";
    }
}
