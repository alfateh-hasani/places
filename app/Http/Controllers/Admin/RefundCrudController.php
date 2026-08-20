<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\ProcessGeideaRefundJob;
use App\Models\Refund;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

class RefundCrudController extends CrudController
{
    use ListOperation;
    use ShowOperation;

    public function setup()
    {
        CRUD::setModel(Refund::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/refund');
        CRUD::setEntityNameStrings(__('cms.refund'), __('cms.refunds'));

        if (! backpack_user()->can('refund.list')) {
            abort(403, 'Unauthorized Access');
        }

        CRUD::denyAccess(['create', 'update', 'delete']);
        CRUD::addClause('with', 'booking.customer', 'booking.apartment');
        CRUD::addClause('orderBy', 'created_at', 'desc');
    }

    protected function setupListOperation()
    {
        $this->addKpiWidgets();

        CRUD::addColumn([
            'name' => 'booking_number',
            'type' => 'custom_html',
            'label' => __('cms.number_of_booking'),
            'value' => fn ($entry) => e($entry->booking?->number_of_booking ?? '—'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('booking', function ($q) use ($searchTerm) {
                    $q->where('number_of_booking', 'like', "%{$searchTerm}%");
                })->orWhere('order_id', 'like', "%{$searchTerm}%");
            },
        ]);

        CRUD::addColumn([
            'name' => 'customer',
            'type' => 'custom_html',
            'label' => __('cms.customer'),
            'value' => fn ($entry) => e($this->customerName($entry)),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('booking', function ($q) use ($searchTerm) {
                    $q->where('customer_full_name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('customer', function ($c) use ($searchTerm) {
                            $c->where('first_name', 'like', "%{$searchTerm}%")
                                ->orWhere('last_name', 'like', "%{$searchTerm}%");
                        });
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'unit',
            'type' => 'custom_html',
            'label' => __('cms.apartment'),
            'value' => fn ($entry) => e($entry->booking?->apartment?->unit_number ?? '—'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('booking.apartment', function ($q) use ($searchTerm) {
                    $q->where('unit_number', 'like', "%{$searchTerm}%");
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'amount',
            'type' => 'custom_html',
            'label' => __('cms.refund_amount'),
            'value' => fn ($entry) => '<span class="font-weight-bold">'.number_format((float) $entry->amount, 2).' '.e($entry->currency).'</span>',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('amount', 'like', "%{$searchTerm}%");
            },
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'type' => 'custom_html',
            'label' => __('cms.refund_status'),
            'value' => fn ($entry) => $this->statusBadge($entry->status),
        ]);

        CRUD::addColumn([
            'name' => 'booking_status',
            'type' => 'custom_html',
            'label' => __('cms.booking_status'),
            'value' => fn ($entry) => $this->bookingStatusBadge($entry->booking?->status),
        ]);

        CRUD::addColumn([
            'name' => 'attempts',
            'type' => 'number',
            'label' => __('cms.refund_attempts'),
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'type' => 'datetime',
            'label' => __('cms.last_attempt'),
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'datetime',
            'label' => __('cms.created_at'),
        ]);

        CRUD::addFilter([
            'name' => 'status',
            'type' => 'dropdown',
            'label' => __('cms.refund_status'),
        ], [
            'pending' => __('cms.refund_status_pending'),
            'processing' => __('cms.refund_status_processing'),
            'refunded' => __('cms.refund_status_approved'),
            'failed' => __('cms.refund_status_failed'),
        ], function ($value) {
            CRUD::addClause('where', 'status', $value);
        });

        CRUD::addButtonFromView('line', 'refund_retry', 'refund_retry', 'end');
    }

    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name' => 'booking_number',
            'type' => 'custom_html',
            'label' => __('cms.number_of_booking'),
            'value' => fn ($entry) => e($entry->booking?->number_of_booking ?? '—'),
        ]);
        CRUD::addColumn([
            'name' => 'customer',
            'type' => 'custom_html',
            'label' => __('cms.customer'),
            'value' => fn ($entry) => e($this->customerName($entry)),
        ]);
        CRUD::addColumn([
            'name' => 'status',
            'type' => 'custom_html',
            'label' => __('cms.refund_status'),
            'value' => fn ($entry) => $this->statusBadge($entry->status),
        ]);
        CRUD::addColumn([
            'name' => 'booking_status',
            'type' => 'custom_html',
            'label' => __('cms.booking_status'),
            'value' => fn ($entry) => $this->bookingStatusBadge($entry->booking?->status),
        ]);
        CRUD::addColumn([
            'name' => 'timeline',
            'type' => 'custom_html',
            'label' => __('cms.refund_timeline'),
            'value' => fn ($entry) => $this->timeline($entry),
        ]);
        CRUD::addColumn(['name' => 'amount', 'type' => 'number', 'label' => __('cms.refund_amount'), 'suffix' => ' '.'SAR']);
        CRUD::addColumn(['name' => 'order_id', 'type' => 'text', 'label' => 'Gateway Order ID']);
        CRUD::addColumn(['name' => 'gateway_refund_id', 'type' => 'text', 'label' => 'Gateway Refund ID']);
        CRUD::addColumn(['name' => 'attempts', 'type' => 'number', 'label' => __('cms.refund_attempts')]);
        CRUD::addColumn(['name' => 'error_message', 'type' => 'text', 'label' => __('cms.refund_error')]);
        CRUD::addColumn([
            'name' => 'gateway_result',
            'type' => 'custom_html',
            'label' => __('cms.gateway_result'),
            'value' => fn ($entry) => $this->gatewayResultBlock($entry),
        ]);
        // مخفي مؤقتاً (للمبرمجين) — يمكن إعادة تفعيله لاحقاً بإزالة التعليق
        // CRUD::addColumn([
        //     'name' => 'technical_details',
        //     'type' => 'custom_html',
        //     'label' => __('cms.technical_details'),
        //     'value' => fn ($entry) => $this->technicalDetails($entry),
        // ]);
    }

    /**
     * Staff-friendly summary of the gateway result (no raw JSON).
     */
    private function gatewayResultBlock(Refund $entry): string
    {
        $resp = $entry->response_payload;
        if (empty($resp)) {
            return '<span class="text-muted">— '.__('cms.gateway_not_sent').' —</span>';
        }

        $success = (bool) data_get($resp, 'success');
        $message = data_get($resp, 'message');
        $code = data_get($resp, 'data.responseCode') ?? data_get($resp, 'error.responseCode');
        $gwMessage = data_get($resp, 'data.responseMessage')
            ?? data_get($resp, 'data.detailedResponseMessage')
            ?? data_get($resp, 'error.detailedResponseMessage')
            ?? data_get($resp, 'error.responseMessage');

        $result = $success
            ? '<span style="color:#28a745;font-weight:600;"><i class="la la-check-circle"></i> '.__('cms.gateway_ok').'</span>'
            : '<span style="color:#e74c3c;font-weight:600;"><i class="la la-times-circle"></i> '.__('cms.gateway_not_ok').'</span>';

        $rows = '<tr><th style="width:35%;">'.__('cms.gateway_result').'</th><td>'.$result.'</td></tr>';
        if ($message) {
            $rows .= '<tr><th>'.__('cms.message').'</th><td>'.e($message).'</td></tr>';
        }
        if ($gwMessage) {
            $rows .= '<tr><th>'.__('cms.gateway_message').'</th><td dir="ltr" style="text-align:right;">'.e($gwMessage).'</td></tr>';
        }
        if ($code) {
            $rows .= '<tr><th>'.__('cms.gateway_code').'</th><td dir="ltr" style="text-align:right;">'.e($code).'</td></tr>';
        }

        return '<table class="table table-bordered mb-0">'.$rows.'</table>';
    }

    /**
     * Raw JSON payloads, hidden inside a collapsed section (for developers only).
     */
    private function technicalDetails(Refund $entry): string
    {
        if (empty($entry->request_payload) && empty($entry->response_payload)) {
            return '<span class="text-muted">—</span>';
        }

        return '<details style="cursor:pointer;">'
            .'<summary class="text-muted" style="user-select:none;"><i class="la la-code"></i> '.__('cms.technical_details_hint').'</summary>'
            .'<div class="mt-2"><strong>'.__('cms.gateway_request').'</strong>'.$this->jsonBlock($entry->request_payload).'</div>'
            .'<div class="mt-2"><strong>'.__('cms.gateway_response').'</strong>'.$this->jsonBlock($entry->response_payload).'</div>'
            .'</details>';
    }

    /**
     * Retry a failed refund by re-dispatching the (idempotent) refund job.
     */
    public function retry($id)
    {
        if (! backpack_user()->can('refund.retry')) {
            abort(403, 'Unauthorized Access');
        }

        $refund = Refund::findOrFail($id);

        if ($refund->status !== Refund::STATUS_FAILED) {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        ProcessGeideaRefundJob::dispatch($refund->booking_id);
        \Alert::success(__('cms.refund_retry_started'))->flash();

        return back();
    }

    private function addKpiWidgets(): void
    {
        $counts = Refund::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $totalRefunded = (float) Refund::where('status', Refund::STATUS_REFUNDED)->sum('amount');

        $tiles = [
            ['label' => __('cms.refund_status_approved'), 'value' => (int) ($counts['refunded'] ?? 0), 'color' => '#28a745', 'icon' => 'la-check-circle'],
            ['label' => __('cms.refund_status_processing'), 'value' => (int) ($counts['processing'] ?? 0), 'color' => '#3498db', 'icon' => 'la-spinner'],
            ['label' => __('cms.refund_status_failed'), 'value' => (int) ($counts['failed'] ?? 0), 'color' => '#e74c3c', 'icon' => 'la-times-circle'],
            ['label' => __('cms.total_refunded'), 'value' => number_format($totalRefunded, 2).' SAR', 'color' => '#34495e', 'icon' => 'la-money-bill-wave'],
        ];

        Widget::add([
            'type' => 'view',
            'view' => 'admin.refunds.kpi',
            'tiles' => $tiles,
        ])->to('before_content');
    }

    private function customerName(Refund $entry): string
    {
        $c = $entry->booking?->customer;
        $name = trim(($c?->first_name ?? '').' '.($c?->last_name ?? ''));

        return $name !== '' ? $name : ($entry->booking?->customer_full_name ?? '—');
    }

    private function statusBadge(?string $status): string
    {
        $labels = [
            'pending' => __('cms.refund_status_pending'),
            'processing' => __('cms.refund_status_processing'),
            'refunded' => __('cms.refund_status_approved'),
            'failed' => __('cms.refund_status_failed'),
        ];
        $colors = [
            'pending' => '#f0ad4e',
            'processing' => '#3498db',
            'refunded' => '#28a745',
            'failed' => '#e74c3c',
        ];
        $icons = [
            'pending' => 'la-clock',
            'processing' => 'la-spinner',
            'refunded' => 'la-check-circle',
            'failed' => 'la-times-circle',
        ];
        $color = $colors[$status] ?? '#6c757d';
        $label = $labels[$status] ?? ucfirst((string) $status);
        $icon = $icons[$status] ?? 'la-question';

        return "<span class='badge' style='background-color:{$color};color:#fff;padding:.45em .7em;font-size:.82rem;font-weight:600;border-radius:6px;'><i class='la {$icon}' style='font-size:1.05rem;vertical-align:-2px;'></i> {$label}</span>";
    }

    private function bookingStatusBadge(?string $status): string
    {
        $labels = [
            'pending' => __('cms.status_pending'),
            'approved' => __('cms.status_approved'),
            'booked' => __('cms.status_booked'),
            'finished' => __('cms.status_finished'),
            'canceled' => __('cms.status_canceled'),
            'customer_canceled' => __('cms.status_customer_canceled'),
            'rejected' => __('cms.status_rejected'),
        ];
        $colors = [
            'pending' => '#6c757d',
            'approved' => '#28a745',
            'booked' => '#007bff',
            'finished' => '#343a40',
            'canceled' => '#dc3545',
            'customer_canceled' => '#fd7e14',
            'rejected' => '#b02a37',
        ];
        $icons = [
            'pending' => 'la-clock',
            'approved' => 'la-check-circle',
            'booked' => 'la-calendar-check',
            'finished' => 'la-flag-checkered',
            'canceled' => 'la-ban',
            'customer_canceled' => 'la-user-times',
            'rejected' => 'la-times-circle',
        ];
        $color = $colors[$status] ?? '#6c757d';
        $icon = $icons[$status] ?? 'la-info-circle';
        $label = $labels[$status] ?? ucfirst((string) $status);

        return "<span class='badge' style='background-color:{$color};color:#fff;padding:.45em .7em;font-size:.82rem;font-weight:600;border-radius:6px;'><i class='la {$icon}' style='font-size:1.05rem;vertical-align:-2px;'></i> {$label}</span>";
    }

    private function timeline(Refund $entry): string
    {
        $requested = optional($entry->created_at)->format('Y-m-d H:i');
        $updated = optional($entry->updated_at)->format('Y-m-d H:i');
        $end = match ($entry->status) {
            'refunded' => "<span class='text-success'>● ".__('cms.refund_status_approved')." {$updated}</span>",
            'failed' => "<span class='text-danger'>● ".__('cms.refund_status_failed')." {$updated}</span>",
            'processing' => "<span class='text-info'>● ".__('cms.refund_status_processing')." {$updated}</span>",
            default => "<span class='text-warning'>● ".__('cms.refund_status_pending').'</span>',
        };

        return "<span class='text-muted'>● ".__('cms.refund_status_pending')." {$requested}</span> &nbsp;→&nbsp; {$end}";
    }

    private function jsonBlock(mixed $payload): string
    {
        if (empty($payload)) {
            return '<span class="text-muted">—</span>';
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<pre dir="ltr" style="max-height:320px;overflow:auto;background:#f7f7f9;padding:10px;border-radius:6px;">'.e($json).'</pre>';
    }
}
