<?php

namespace App\Http\Controllers\Admin;

use App\Models\Transaction;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class TransactionController extends CrudController
{
    public function setup()
    {
        CRUD::setModel(Transaction::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/transaction');
        CRUD::setEntityNameStrings('transaction', 'transactions');
    }

    protected function setupListOperation()
    {
        // Customer ID with icon
        CRUD::addColumn([
            'name' => 'customer_id',
            'type' => 'text',
            'label' => 'Customer <i class="la la-user"></i>',
        ]);

        // Booking ID with icon
        CRUD::addColumn([
            'name' => 'booking_id',
            'type' => 'text',
            'label' => 'Booking <i class="la la-book"></i>',
        ]);

        // Transaction Reference
        CRUD::addColumn([
            'name' => 'transaction_reference',
            'type' => 'text',
            'label' => 'Reference <i class="la la-hashtag"></i>',
        ]);

        // Amount with formatted currency
        CRUD::addColumn([
            'name' => 'amount',
            'type' => 'custom_html',
            'label' => 'Amount (SAR) <i class="la la-money"></i>',
            'value' => function ($entry) {
                return '<span class="text-primary font-weight-bold">' . number_format($entry->amount, 2) . ' ' . $entry->currency . '</span>';
            }
        ]);

        // Type with colored badges
        CRUD::addColumn([
            'name' => 'type',
            'type' => 'custom_html',
            'label' => 'Type <i class="la la-exchange"></i>',
            'value' => function ($entry) {
                $color = $entry->type === 'deposit' ? 'success' : 'danger';
                return "<span class='badge badge-{$color}'>" . ucfirst($entry->type) . "</span>";
            }
        ]);

        // Status with colored badges
        CRUD::addColumn([
            'name' => 'status',
            'type' => 'custom_html',
            'label' => 'Status <i class="la la-info-circle"></i>',
            'value' => function ($entry) {
                $statusColors = [
                    'pending' => 'warning',
                    'completed' => 'success',
                    'failed' => 'danger',
                ];
                $color = $statusColors[$entry->status] ?? 'secondary';
                return "<span class='badge badge-{$color}'>" . ucfirst($entry->status) . "</span>";
            }
        ]);

        // Payment Gateway with icons
        CRUD::addColumn([
            'name' => 'payment_gateway',
            'type' => 'enum',
            'label' => 'Payment Gateway <i class="la la-credit-card"></i>',
        ]);

        // Created At with date formatting
        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'custom_html',
            'label' => 'Created <i class="la la-calendar"></i>',
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
            'name' => 'transaction_details',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '
                    <h5><strong>Transaction Details</strong></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Customer <i class="la la-user"></i></th>
                            <td>' . optional($entry->customer)->first_name . '</td>
                        </tr>
                        <tr>
                            <th>Booking <i class="la la-book"></i></th>
                            <td>' . $entry->booking_id . '</td>
                        </tr>
                        <tr>
                            <th>Reference <i class="la la-hashtag"></i></th>
                            <td>' . $entry->transaction_reference . '</td>
                        </tr>
                        <tr>
                            <th>Amount <i class="la la-money"></i></th>
                            <td><span class="text-primary font-weight-bold">' . number_format($entry->amount, 2) . ' ' . $entry->currency . '</span></td>
                        </tr>
                        <tr>
                            <th>Status <i class="la la-info-circle"></i></th>
                            <td>' . $this->getStatusBadge($entry->status) . '</td>
                        </tr>
                    </table>';
            }
        ]);
    }

    protected function getStatusBadge($status)
    {
        $statusLabels = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
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
