<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;

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

        // شاشة إجراء بخطوتين:
        // 1) status=customer_canceled  → بحاجة إلى إلغاء (OwnerRez أو محلي)
        // 2) status=canceled           → أُلغي، وبحاجة إلى استرداد المبلغ
        // كلاهما بـ refund_status=pending. بعد الاسترداد/الرفض يخرج من القائمة.
        CRUD::addClause('whereIn', 'status', ['customer_canceled', 'canceled']);
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

        // حالة الحجز — تُبيّن الخطوة المطلوبة: customer_canceled = بحاجة إلغاء، canceled = بحاجة استرداد
        CRUD::addColumn([
            'name' => 'status',
            'type' => 'custom_html',
            'label' => __('cms.booking_status'),
            'value' => function ($entry) {
                return $this->getBookingStepBadge($entry->status);
            },
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

        // أزرار الإجراءات — خطوتان:
        // الخطوة 1 (الإلغاء) عندما status=customer_canceled:
        //   - وحدة مربوطة بـ OwnerRez → رابط الإلغاء في OwnerRez (الويبهوك يُنهي الإلغاء محلياً).
        //   - وحدة غير مربوطة        → زر إلغاء محلي.
        //   - رفض الطلب.
        // الخطوة 2 (الاسترداد) عندما status=canceled: زر استرداد يفتح نافذة تحديد المبلغ (كامل/جزئي).
        CRUD::addButtonFromView('line', 'ownerrez_deeplink', 'ownerrez_deeplink', 'end');
        CRUD::addButtonFromView('line', 'cancel_local', 'cancel_local', 'end');
        CRUD::addButtonFromView('line', 'reject_refund', 'reject_refund', 'end');
        CRUD::addButtonFromView('line', 'refund_action', 'refund_action', 'end');

        // نافذة تحديد مبلغ الاسترداد (تُضاف مرة واحدة). نافذة مخفية؛ before_content مضمون العرض.
        Widget::add([
            'type' => 'view',
            'view' => 'admin.refunds.refund_modal',
        ])->to('before_content');
    }

    /**
     * الخطوة 1 (وحدة غير مربوطة بـ OwnerRez): إلغاء محلي فقط — يحرّر الوحدة بلا استرداد.
     * الوحدات المربوطة تُلغى في OwnerRez (الويبهوك يُنهي الإلغاء).
     */
    public function cancelLocal($id)
    {
        $booking = $this->authorizedBooking($id);

        if ($booking->status !== 'customer_canceled' || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        if (! empty($booking->ownerrez_booking_id)) {
            \Alert::error(__('cms.use_ownerrez_to_cancel'))->flash();

            return back();
        }

        // إلغاء محلي فقط — تتحرّر الوحدة، ويبقى refund_status=pending للخطوة الثانية (الاسترداد).
        $booking->update(['status' => 'canceled']);

        \Alert::success(__('cms.booking_canceled_now_refund'))->flash();

        return back();
    }

    /**
     * الخطوة 2: استرداد المبلغ بعد الإلغاء — كامل أو جزئي (0 < amount ≤ المبلغ الكامل).
     */
    public function processRefund($id, Request $request)
    {
        $booking = $this->authorizedBooking($id);

        if ($booking->status !== 'canceled' || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:'.(float) $booking->final_price],
        ], [], ['amount' => __('cms.refund_amount')]);

        // المبلغ المُرسل من النافذة (كامل عند إخفاء الجزئي).
        $booking->update(['refund_amount' => $validated['amount']]);

        $this->runRefund($booking);

        return back();
    }

    /**
     * ينفّذ الاسترداد ويعرض نتيجة واضحة للموظف (بدون 500).
     */
    private function runRefund(Booking $booking): void
    {
        try {
            $outcome = app(\App\Actions\Refunds\ProcessBookingRefund::class)->execute($booking->fresh());
        } catch (\Throwable $e) {
            \Alert::error(__('cms.refund_failed').': '.$e->getMessage())->flash();

            return;
        }

        $fresh = $booking->fresh();
        match ($outcome) {
            'approved' => \Alert::success(__('cms.refund_done'))->flash(),
            'processing' => \Alert::warning(__('cms.refund_processing_flash'))->flash(),
            default => \Alert::error(__('cms.refund_failed').': '.($fresh->refund_error ?: ''))->flash(),
        };
    }

    /**
     * رفض طلب الإلغاء وإعادة الحجز نشطاً — آمن ضد الحجز المزدوج (الوحدة بقيت محجوزة طوال المراجعة).
     */
    public function reject($id)
    {
        $booking = $this->authorizedBooking($id);

        if ($booking->status !== 'customer_canceled' || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $booking->update(['refund_status' => 'rejected', 'status' => 'approved']);

        \Alert::success(__('cms.refund_rejected_successfully'))->flash();

        return back();
    }

    private function authorizedBooking($id): Booking
    {
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        return Booking::findOrFail($id);
    }

    private function getBookingStepBadge(?string $status): string
    {
        if ($status === 'customer_canceled') {
            return "<span class='badge' style='background-color:#fd7e14;color:#fff;padding:.4em .6em;border-radius:6px;'><i class='la la-hourglass-half'></i> ".__('cms.step_needs_cancel').'</span>';
        }
        if ($status === 'canceled') {
            return "<span class='badge' style='background-color:#3498db;color:#fff;padding:.4em .6em;border-radius:6px;'><i class='la la-money-bill-wave'></i> ".__('cms.step_needs_refund').'</span>';
        }

        return "<span class='badge badge-secondary'>{$status}</span>";
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
