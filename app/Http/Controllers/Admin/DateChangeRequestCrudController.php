<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DateChanges\ProcessDateChangeRefund;
use App\Enums\DateChangeStatus;
use App\Models\DateChangeRequest;
use App\Services\DateChangeService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Support\Facades\Log;

/**
 * Admin review screen for customer date-change requests.
 *
 * Focus is the "cheaper" path (delta < 0): staff approve → apply the new dates → refund the
 * difference (idempotent, retryable). Surcharge/even requests settle themselves and only appear
 * here if a refund is stuck (failed/processing).
 */
class DateChangeRequestCrudController extends CrudController
{
    use ListOperation;

    public function setup()
    {
        CRUD::setModel(DateChangeRequest::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/date-change-requests');
        CRUD::setEntityNameStrings(__('cms.date_change_requests'), __('cms.date_change_requests'));

        if (! backpack_user()->can('date_change.list')) {
            abort(403, 'Unauthorized Access');
        }

        // Show all requests, but float the ones needing attention (review / retry / in-flight)
        // to the top, and push the finished ones (applied / rejected) below.
        CRUD::addClause('with', 'booking.customer', 'booking.apartment');

        // Priority order. Values are inlined (NOT bound) on purpose: they are fixed enum constants
        // (no user input), and bound params on orderByRaw break Backpack's search count() query
        // (the count strips ORDER BY but keeps its bindings → SQLSTATE[HY093]).
        $priority = collect([
            DateChangeStatus::PendingReview,
            DateChangeStatus::Failed,
            DateChangeStatus::Processing,
            DateChangeStatus::AwaitingPayment,
            DateChangeStatus::Pending,
            DateChangeStatus::Applied,
            DateChangeStatus::Rejected,
        ])->map(fn (DateChangeStatus $s) => "'".$s->value."'")->implode(', ');

        // COALESCE(NULLIF(FIELD(...),0),999): known statuses keep their priority; any
        // unknown/legacy value (FIELD → 0) falls to the bottom instead of the top.
        CRUD::addClause('orderByRaw', "COALESCE(NULLIF(FIELD(status, {$priority}), 0), 999) ASC, created_at DESC");
    }

    protected function setupListOperation()
    {
        $this->crud->removeAllButtons();
        $this->crud->removeAllButtonsFromStack('line');

        // ملاحظة: أعمدة custom_html محسوبة/علائقية وليست أعمدة قاعدة بيانات، فنمنع الفرز/البحث عليها
        // (الترتيب الافتراضي عبر orderByRaw) وإلا حاولت DataTables ORDER BY على عمود غير موجود → خطأ Ajax.
        // Searchable via a relation closure (safe — searchLogic uses whereHas, never ORDER BY the
        // computed column). Ordering stays disabled to avoid "Unknown column" on these columns.
        CRUD::addColumn([
            'name' => 'booking_number',
            'type' => 'custom_html',
            'label' => __('cms.number_of_booking'),
            'orderable' => false,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('booking', function ($q) use ($searchTerm) {
                    $q->where('number_of_booking', 'like', "%{$searchTerm}%");
                });
            },
            'value' => fn ($entry) => e($entry->booking?->number_of_booking ?? '—'),
        ]);

        CRUD::addColumn([
            'name' => 'customer',
            'type' => 'custom_html',
            'label' => __('cms.customer'),
            'orderable' => false,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('booking', function ($q) use ($searchTerm) {
                    $q->where('customer_full_name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('customer', function ($c) use ($searchTerm) {
                            $c->where('first_name', 'like', "%{$searchTerm}%")
                                ->orWhere('last_name', 'like', "%{$searchTerm}%");
                        });
                });
            },
            'value' => fn ($entry) => e($this->customerName($entry)),
        ]);

        CRUD::addColumn([
            'name' => 'apartment',
            'type' => 'custom_html',
            'label' => __('cms.apartment'),
            'orderable' => false,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('booking.apartment', function ($q) use ($searchTerm) {
                    $q->where('name_ar', 'like', "%{$searchTerm}%")
                        ->orWhere('name_en', 'like', "%{$searchTerm}%")
                        ->orWhere('unit_number', 'like', "%{$searchTerm}%");
                });
            },
            'value' => fn ($entry) => e($entry->booking?->apartment?->name_ar ?? '—'),
        ]);

        CRUD::addColumn([
            'name' => 'original_dates',
            'type' => 'custom_html',
            'label' => __('cms.current_dates'),
            'orderable' => false,
            'searchable' => false,
            'value' => fn ($entry) => $entry->original_check_in->format('Y-m-d').' → '.$entry->original_check_out->format('Y-m-d'),
        ]);

        CRUD::addColumn([
            'name' => 'new_dates',
            'type' => 'custom_html',
            'label' => __('cms.new_dates'),
            'orderable' => false,
            'searchable' => false,
            'value' => fn ($entry) => '<strong>'.$entry->new_check_in->format('Y-m-d').' → '.$entry->new_check_out->format('Y-m-d').'</strong>',
        ]);

        CRUD::addColumn([
            'name' => 'price_delta',
            'type' => 'custom_html',
            'label' => __('cms.price_difference'),
            'orderable' => false,
            'searchable' => false,
            'value' => function ($entry) {
                $delta = (float) $entry->price_delta;
                $color = $delta < 0 ? '#28a745' : ($delta > 0 ? '#e74c3c' : '#6c757d');
                $sign = $delta < 0 ? '-' : ($delta > 0 ? '+' : '');

                return "<span style='color:{$color};font-weight:bold;'>{$sign}".number_format(abs($delta), 2).' SAR</span>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'type' => 'custom_html',
            'label' => __('cms.status'),
            'orderable' => false,
            'searchable' => false,
            'value' => fn ($entry) => $this->statusBadge($entry->status),
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'datetime',
            'label' => __('cms.created_at'),
            'orderable' => false,
            'searchable' => false,
        ]);

        CRUD::addButtonFromView('line', 'date_change_approve', 'date_change_approve', 'end');
        CRUD::addButtonFromView('line', 'date_change_reject', 'date_change_reject', 'end');
        CRUD::addButtonFromView('line', 'date_change_retry', 'date_change_retry', 'end');

        // Shared JS that submits the line actions with a dynamically-built CSRF form
        // (avoids leaking the token as text inside the DataTables cell).
        Widget::add(['type' => 'view', 'view' => 'admin.date_changes.actions_script'])->to('before_content');
    }

    /**
     * Approve a "cheaper" request: apply the new dates, then refund the difference.
     */
    public function approve($id)
    {
        $request = $this->authorized($id);

        if ($request->status !== DateChangeStatus::PendingReview->value) {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        try {
            app(DateChangeService::class)->applyDates($request);
        } catch (\Throwable $e) {
            Log::error('Date change apply failed', ['request_id' => $request->id, 'error' => $e->getMessage()]);
            \Alert::error(__('cms.date_change_apply_failed'))->flash();

            return back();
        }

        $request->update([
            'status' => DateChangeStatus::Processing->value,
            'reviewed_by' => backpack_user()->id,
            'reviewed_at' => now(),
        ]);

        $this->runRefund($request);

        return back();
    }

    /**
     * Retry a failed request. Re-applies the dates when they were never applied (e.g. surcharge
     * paid but OwnerRez sync failed — we retry, we do NOT auto-refund a transient failure);
     * retries the difference refund when the dates were already applied.
     */
    public function retry($id)
    {
        $request = $this->authorized($id);

        if ($request->status !== DateChangeStatus::Failed->value) {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        try {
            $outcome = app(DateChangeService::class)->retrySettlement($request);
        } catch (\Throwable $e) {
            Log::error('Date change retry failed', ['request_id' => $request->id, 'error' => $e->getMessage()]);
            \Alert::error(__('cms.date_change_apply_failed'))->flash();

            return back();
        }

        match ($outcome) {
            'applied' => \Alert::success(__('cms.date_change_done'))->flash(),
            'processing' => \Alert::warning(__('cms.refund_processing_flash'))->flash(),
            default => \Alert::error(__('cms.refund_failed'))->flash(),
        };

        return back();
    }

    /**
     * Reject/cancel the request — booking keeps its original dates. Safe: the window stayed reserved.
     * Also the admin override for a stuck surcharge (awaiting_payment) that the customer never paid.
     */
    public function reject($id)
    {
        $request = $this->authorized($id);

        $rejectable = [
            DateChangeStatus::PendingReview->value,
            DateChangeStatus::AwaitingPayment->value,
            DateChangeStatus::Pending->value,
        ];

        if (! in_array($request->status, $rejectable, true)) {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $request->update([
            'status' => DateChangeStatus::Rejected->value,
            'reviewed_by' => backpack_user()->id,
            'reviewed_at' => now(),
        ]);

        \Alert::success(__('cms.date_change_rejected'))->flash();

        return back();
    }

    private function runRefund(DateChangeRequest $request): void
    {
        try {
            $outcome = app(ProcessDateChangeRefund::class)->execute($request->fresh());
        } catch (\Throwable $e) {
            // Never surface raw gateway/exception text to the admin — log it, show a friendly message.
            Log::error('Date change refund failed', ['request_id' => $request->id, 'error' => $e->getMessage()]);
            \Alert::error(__('cms.refund_failed'))->flash();

            return;
        }

        match ($outcome) {
            'applied' => \Alert::success(__('cms.date_change_done'))->flash(),
            'processing' => \Alert::warning(__('cms.refund_processing_flash'))->flash(),
            default => \Alert::error(__('cms.refund_failed'))->flash(),
        };
    }

    private function authorized($id): DateChangeRequest
    {
        if (! backpack_user()->can('date_change.manage')) {
            abort(403, 'Unauthorized Access');
        }

        return DateChangeRequest::findOrFail($id);
    }

    private function customerName(DateChangeRequest $entry): string
    {
        $c = $entry->booking?->customer;
        $name = trim(($c?->first_name ?? '').' '.($c?->last_name ?? ''));

        return $name !== '' ? $name : ($entry->booking?->customer_full_name ?? '—');
    }

    private function statusBadge(?string $status): string
    {
        $labels = [
            'pending_review' => __('cms.date_change_status_pending_review'),
            'awaiting_payment' => __('cms.date_change_status_awaiting_payment'),
            'processing' => __('cms.refund_status_processing'),
            'applied' => __('cms.date_change_status_applied'),
            'approved' => __('cms.date_change_status_applied'), // legacy alias for applied
            'rejected' => __('cms.refund_status_rejected'),
            'failed' => __('cms.refund_status_failed'),
        ];
        $colors = [
            'pending_review' => '#fd7e14',
            'awaiting_payment' => '#6f42c1',
            'processing' => '#3498db',
            'applied' => '#28a745',
            'approved' => '#28a745',
            'rejected' => '#b02a37',
            'failed' => '#e74c3c',
        ];
        $color = $colors[$status] ?? '#6c757d';
        $label = $labels[$status] ?? __('cms.date_change_status_applied');

        return "<span class='badge' style='background-color:{$color};color:#fff;padding:.45em .7em;font-weight:600;border-radius:6px;'>{$label}</span>";
    }
}
