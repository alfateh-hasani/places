<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/canceled-bookings');
        CRUD::setEntityNameStrings(__('cms.canceled_bookings'), __('cms.canceled_bookings'));
        
        if (!backpack_user()->can('booking.list')) {
            abort(403, 'Unauthorized Access');
        }
        
        // تصفية الحجوزات: فقط customer_canceled مع refund_status = pending
        CRUD::addClause('where', 'status', 'customer_canceled');
        CRUD::addClause('where', 'refund_status', 'pending');
        CRUD::addClause('orderBy', 'created_at', 'desc');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
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
            'value' => function($entry) {
                return '<span class="text-danger font-weight-bold">' . number_format($entry->refund_amount, 2) . ' SAR</span>';
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'refund_status',
            'type' => 'custom_html',
            'label' => __('cms.refund_status'),
            'value' => function($entry) {
                return $this->getRefundStatusBadge($entry->refund_status);
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'datetime',
            'label' => __('cms.canceled_at'),
        ]);

        // إضافة أزرار الإجراءات
        CRUD::addButtonFromView('line', 'approve_refund', 'approve_refund', 'end');
        CRUD::addButtonFromView('line', 'reject_refund', 'reject_refund', 'end');
    }

    /**
     * معالجة الاسترداد (تأكيد أو رفض) - يدوي من قبل المحاسب
     */
    public function processRefund($id, $action)
    {
        // التحقق من الصلاحيات
        if (!backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        $booking = \App\Models\Booking::findOrFail($id);

        // التحقق من أن الحجز في الحالة الصحيحة
        if ($booking->status !== 'customer_canceled' || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();
            return back();
        }

        if ($action === 'approve') {
            // تأكيد الاسترداد (المحاسب قام بالاسترداد يدوياً في جيديا)
            $booking->refund_status = 'approved';
            $booking->refund_date = now();
            $booking->save();
            
            \Alert::success(__('cms.refund_approved_successfully'))->flash();
        } elseif ($action === 'reject') {
            // رفض الاسترداد
            $booking->refund_status = 'rejected';
            $booking->status = 'approved'; // إعادة الحجز إلى حالة approved
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
            'approved' => __('cms.refund_status_approved'),
            'rejected' => __('cms.refund_status_rejected'),
        ];
        $refundStatusColors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];
        $color = $refundStatusColors[$refundStatus] ?? 'info';
        $label = $refundStatusLabels[$refundStatus] ?? ucfirst($refundStatus);
        return "<span class='badge badge-{$color}'>{$label}</span>";
    }
}
