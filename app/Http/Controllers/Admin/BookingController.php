<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ApartmentRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApartmentController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BookingController extends CrudController
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
        CRUD::setModel(\App\Models\Booking::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/booking');
        CRUD::setEntityNameStrings(__('cms.booking_management'), __('cms.booking_management'));
        CRUD::denyAccess(['create', 'delete','update']);

    }
    

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->addStatusFilter();
        $this->addPaymentStatusFilter();
        CRUD::addButtonFromModelFunction('line', 'changeStatus', 'getChangeStatusButton', 'end');
        CRUD::addButtonFromModelFunction('line', 'changePaymentStatus', 'getChangePaymentStatusButton', 'end');
    
        // Customer column
        CRUD::addColumn([
            'name' => 'customer_id',
            'type' => 'select',
            'label' => __('cms.customer') . ' <i class="la la-user"></i>',
            'entity' => 'customer',
            'attribute' => 'first_name',
            'model' => \App\Models\Customer::class,
        ]);
      // Status with badge
      CRUD::addColumn([
        'name' => 'status',
        'label' => __('cms.status') . ' <i class="la la-info-circle"></i>',
        'type' => 'custom_html',
        'value' => function($entry) {
            return $this->getStatusBadge($entry->status);
        }
    ]);

    // Payment status with badge
    CRUD::addColumn([
        'name' => 'payment_status',
        'label' => __('cms.payment_status') . ' <i class="la la-credit-card"></i>',
        'type' => 'custom_html',
        'value' => function($entry) {
            return $this->getPaymentStatusBadge($entry->payment_status);
        }
    ]);
        // Apartment column
        CRUD::addColumn([
            'name' => 'apartment_id',
            'type' => 'select',
            'label' => __('cms.apartment') . ' <i class="la la-building"></i>',
            'entity' => 'apartment',
            'attribute' => 'name_ar',
            'model' => \App\Models\Apartment::class,
        ]);
    
        // Number of Booking
        CRUD::addColumn([
            'name' => 'number_of_booking',
            'type' => 'text',
            'label' => __('cms.number_of_booking') . ' <i class="la la-bookmark"></i>',
        ]);
    
        // Check-in date
        CRUD::addColumn([
            'name' => 'check_in',
            'type' => 'custom_html',
            'label' => __('cms.check_in') . ' <i class="la la-calendar-check"></i>',
            'value' => function($entry) {
                return '<span class="text-success font-weight-bold">' . \Carbon\Carbon::parse($entry->check_in)->format('Y-m-d') . '</span>';
            }
        ]);
    
        // Check-out date
        CRUD::addColumn([
            'name' => 'check_out',
            'type' => 'custom_html',
            'label' => __('cms.check_out') . ' <i class="la la-calendar-times"></i>',
            'value' => function($entry) {
                return '<span class="text-danger font-weight-bold">' . \Carbon\Carbon::parse($entry->check_out)->format('Y-m-d') . '</span>';
            }
        ]);
    
        // Number of nights
        CRUD::addColumn([
            'name' => 'number_of_nights',
            'type' => 'custom_html',
            'label' => __('cms.number_of_nights') . ' <i class="la la-moon"></i>',
            'value' => function($entry) {
                return "<span class='badge badge-info'>{$entry->number_of_nights}</span>";
            }
        ]);
    
        // Total Price
        CRUD::addColumn([
            'name' => 'total_price',
            'type' => 'custom_html',
            'label' => __('cms.total_price') . ' (SAR) <i class="la la-money"></i>',
            'value' => function($entry) {
                return '<span class="text-primary font-weight-bold">' . number_format($entry->total_price, 2) . ' SAR</span>';
            }
        ]);
    
        // Final Price
        CRUD::addColumn([
            'name' => 'final_price',
            'type' => 'custom_html',
            'label' => __('cms.final_price') . ' (SAR) <i class="la la-money-bill"></i>',
            'value' => function($entry) {
                return '<span class="text-success font-weight-bold">' . number_format($entry->final_price, 2) . ' SAR</span>';
            }
        ]);
    
      
    
        // Adults count
        CRUD::addColumn([
            'name' => 'adults_count',
            'type' => 'number',
            'label' => __('cms.adults_count') . ' <i class="la la-user"></i>',
        ]);
    
        // Children count
        CRUD::addColumn([
            'name' => 'children_count',
            'type' => 'number',
            'label' => __('cms.children_count') . ' <i class="la la-child"></i>',
        ]);
    
        // Payment Method
        CRUD::addColumn([
            'name' => 'payment_method_code',
            'type' => 'enum',
            'label' => __('cms.payment_method_code') . ' <i class="la la-wallet"></i>',
        ]);
    }
    
    

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
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
                    <h5><strong>' . __('cms.customer_info') . '</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>' . __('cms.customer') . ' <i class="la la-user"></i></th>
                            <td>' . optional($entry->customer)->first_name . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.email') . ' <i class="la la-envelope"></i></th>
                            <td>' . optional($entry->customer)->email . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.phone') . ' <i class="la la-phone"></i></th>
                            <td>' . optional($entry->customer)->phone . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.apartment') . ' <i class="la la-building"></i></th>
                            <td>' . optional($entry->apartment)->name_ar . '</td>
                        </tr>
                    </table>';
            }
        ]);
    
        // جدول التواريخ وعدد الليالي
        CRUD::addColumn([
            'name' =>  '   تفاصيل&nbsp;  الحجز',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>' . __('cms.booking_details') . '</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>' . __('cms.check_in') . ' <i class="la la-calendar-check"></i></th>
                            <td><span class="badge badge-success">' . \Carbon\Carbon::parse($entry->check_in)->format('d F Y') . '</span></td>
                        </tr>
                        <tr>
                            <th>' . __('cms.check_out') . ' <i class="la la-calendar-times"></i></th>
                            <td><span class="badge badge-danger">' . \Carbon\Carbon::parse($entry->check_out)->format('d F Y') . '</span></td>
                        </tr>
                        <tr>
                            <th>' . __('cms.number_of_nights') . ' <i class="la la-moon"></i></th>
                            <td><span class="badge badge-info">' . $entry->number_of_nights . '</span></td>
                        </tr>
                    </table>';
            }
        ]);
    
        // جدول المعلومات المالية
        CRUD::addColumn([
            'name' => 'المعلومات &nbsp; المالية',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>' . __('cms.financial_info') . '</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>' . __('cms.price_per_night') . ' (SAR) <i class="la la-money"></i></th>
                            <td>' . number_format($entry->price_per_night, 2) . ' SAR</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.total_price') . ' (SAR) <i class="la la-money"></i></th>
                            <td><span class="font-weight-bold text-primary">' . number_format($entry->total_price, 2) . ' SAR</span></td>
                        </tr>
                        <tr>
                            <th>' . __('cms.final_price') . ' (SAR) <i class="la la-money-bill"></i></th>
                            <td><span class="font-weight-bold text-success">' . number_format($entry->final_price, 2) . ' SAR</span></td>
                        </tr>
                        ' . ($entry->discount ? '<tr>
                            <th>' . __('cms.discount') . ' (SAR) <i class="la la-percent"></i></th>
                            <td><span class="font-weight-bold text-danger">' . number_format($entry->discount, 2) . ' SAR</span></td>
                        </tr>' : '') . '
                        ' . ($entry->coupon ? '<tr>
                            <th>' . __('cms.coupon') . ' <i class="la la-tag"></i></th>
                            <td>' . $entry->coupon->code . '</td>
                        </tr>' : '') . '
                    </table>';
            }
        ]);
    
 
        CRUD::addColumn([
            'name' =>   'معلومات &nbsp; الحالة',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>' . __('cms.status_info') . '</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>' . __('cms.status') . '</th>
                            <td>' . $this->getStatusBadge($entry->status) . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.payment_status') . '</th>
                            <td>' . $this->getPaymentStatusBadge($entry->payment_status) . '</td>
                        </tr>
                    </table>';
            }
        ]);
    
        // جدول لعدد البالغين والأطفال وطريقة الدفع
        CRUD::addColumn([
            'name' => 'معلومات &nbsp;&nbsp;إضافية',

            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>' . __('cms.additional_info') . '</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>' . __('cms.adults_count') . ' <i class="la la-user"></i></th>
                            <td>' . $entry->adults_count . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.children_count') . ' <i class="la la-child"></i></th>
                            <td>' . $entry->children_count . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.payment_method_code') . ' <i class="la la-wallet"></i></th>
                            <td>' . $entry->payment_method_code . '</td>
                        </tr>
                    </table>';
            }
        ]);
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
        ];
        $statusColors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'booked' => 'primary',
            'finished' => 'secondary',
            'canceled' => 'dark',
        ];
        $color = $statusColors[$status] ?? 'info';
        $label = $statusLabels[$status] ?? ucfirst($status);
        return "<span class='badge badge-{$color}'>{$label}</span>";
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
    
    protected function addStatusFilter()
    {
        CRUD::addFilter([
            'name' => 'status',
            'type' => 'dropdown',
            'label' => __('cms.status')
        ], [
            'pending' => __('cms.status_pending'),
            'approved' => __('cms.status_approved'),
            'rejected' => __('cms.status_rejected'),
            'booked' => __('cms.status_booked'),
            'finished' => __('cms.status_finished'),
            'canceled' => __('cms.status_canceled')
        ], function($value) {
            CRUD::addClause('where', 'status', $value);
        });
    }
    
    protected function addPaymentStatusFilter()
    {
        CRUD::addFilter([
            'name' => 'payment_status',
            'type' => 'dropdown',
            'label' => __('cms.payment_status')
        ], [
            'pending' => __('cms.payment_status_pending'),
            'paid' => __('cms.payment_status_paid'),
            'failed' => __('cms.payment_status_failed')
        ], function($value) {
            CRUD::addClause('where', 'payment_status', $value);
        });
    }

}
