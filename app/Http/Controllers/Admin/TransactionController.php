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
class TransactionController extends CrudController
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
        CRUD::setModel(\App\Models\Transaction::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/transaction');
        CRUD::setEntityNameStrings(__('cms.transaction'), __('cms.transaction'));
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
        // Customer ID with icon
        CRUD::addColumn([
            'name' => 'customer_id',
            'type' => 'select',
            'label' => __('cms.customer') . ' <i class="la la-user"></i>',
            'entity' => 'customer',
            'attribute' => 'first_name',
            'model' => \App\Models\Customer::class,
        ]);

        // Booking ID with icon
        CRUD::addColumn([
            'name' => 'booking_id',
            'type' => 'text',
            'label' =>__('cms.booking') . ' <i class="la la-book"></i>',
            'value' => function ($entry) {
                return $entry->booking?->number_of_booking ;
            },
        ]);

        // Transaction Reference
        CRUD::addColumn([
            'name' => 'transaction_reference',
            'type' => 'text',
            'label' =>  __('cms.reference') . ' <i class="la la-hashtag"></i>',
        ]);

        // Amount with formatted currency
        CRUD::addColumn([
            'name' => 'amount',
            'type' => 'custom_html',
            'label' =>  __('cms.amount') . '(SAR) <i class="la la-money"></i>',
            'value' => function ($entry) {
                return '<span class="text-primary font-weight-bold">' . number_format($entry->amount, 2) . ' SAR'  . '</span>';
            }
        ]);

     // Type with colored badges
    CRUD::addColumn([
        'name' => 'type',
        'type' => 'custom_html',
        'label' => __('cms.type') . ' <i class="la la-exchange"></i>',
        'value' => function ($entry) {
            $color = $entry->type === 'deposit' ? 'success' : 'danger';
            $typeLabel = $entry->type === 'deposit' ? __('cms.type_deposit') : __('cms.type_withdrawal');
            return "<span class='badge badge-{$color}'>" . ucfirst($typeLabel) . "</span>";
        }
    ]);

    // Status with colored badges
    CRUD::addColumn([
        'name' => 'status',
        'type' => 'custom_html',
        'label' => __('cms.status') . ' <i class="la la-info-circle"></i>',
        'value' => function ($entry) {
            $statusColors = [
                'pending' => 'warning',
                'completed' => 'success',
                'failed' => 'danger',
            ];
            $statusLabels = [
                'pending' => __('cms.status_pending'),
                'completed' => __('cms.status_completed'),
                'failed' => __('cms.status_failed'),
            ];
            $color = $statusColors[$entry->status] ?? 'secondary';
            $label = $statusLabels[$entry->status] ?? ucfirst($entry->status);
            return "<span class='badge badge-{$color}'>{$label}</span>";
        }
    ]);

        // Payment Gateway with icons
        CRUD::addColumn([
            'name' => 'payment_gateway',
            'type' => 'enum',
            'label' => __('cms.payment_gateway') . ' <i class="la la-credit-card"></i>',
            'value' => function ($entry) {
                return __('cms.'.$entry->payment_gateway)   ;
            }
        ]);

        // Created At with date formatting
        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'custom_html',
            'label' => __('cms.created_at') . ' <i class="la la-calendar"></i>',
            'value' => function ($entry) {
                return '<span class="badge badge-info">' . \Carbon\Carbon::parse($entry->created_at)->format('d M Y') . '</span>';
            }
        ]);
    }

    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false); // Disable automatic fields loading

        // Transaction Details Table
        CRUD::addColumn([
            'name' => '',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>' . __('cms.transaction_details') . '</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>' . __('cms.customer') . ' <i class="la la-user"></i></th>
                            <td>' . optional($entry->customer)?->first_name . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.number_of_booking') . ' <i class="la la-book"></i></th>
                            <td>' . $entry->booking?->number_of_booking . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.reference') . ' <i class="la la-hashtag"></i></th>
                            <td>' . $entry->transaction_reference . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.amount') . ' <i class="la la-money"></i></th>
                            <td><span class="text-primary font-weight-bold">' . number_format($entry->amount, 2) . ' ' . $entry->currency . '</span></td>
                        </tr>
                        <tr>
                            <th>' . __('cms.status') . ' <i class="la la-info-circle"></i></th>
                            <td>' . $this->getStatusBadge($entry->status) . '</td>
                        </tr>
                        <tr>
                            <th>' . __('cms.payment_gateway') . ' <i class="la la-credit-card"></i></th>
                            <td>' . __('cms.'.$entry->payment_gateway) . '</td>
                        </tr>
                    </table>';
            }
        ]);
        
    }

    protected function getStatusBadge($status)
    {
        $statusLabels = [
            'pending' => __('cms.status_pending'),
            'completed' => __('cms.status_completed'),
            'failed' => __('cms.status_failed'),
        ];
        $statusColors = [
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
        ];
        $color = $statusColors[$status] ?? 'info';
        $label = $statusLabels[$status] ?? ucfirst($status);
        return "<span class='badge badge-{$color}'>{$label}</span>";
    }
}

