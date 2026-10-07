<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Recover the Geidea gateway orderId for `geidea` transactions that have none — typically
 * records imported from the previous system (ImportProductionBookingsSeeder), which never
 * carried an orderId or gateway response.
 *
 * For each transaction it asks Geidea for the order(s) under the SAME merchantReferenceId
 * (= transaction_reference). A row is only accepted when the returned order matches on
 * reference, is "Paid", and agrees on amount + currency — the same binding the live
 * callback enforces, so a wrong order can never be attached.
 *
 * IMPORTANT: run this with the PRODUCTION Geidea credentials. The sandbox/test keys only
 * see test orders and will report everything as NOT FOUND.
 *
 * Dry run (default):   php artisan geidea:backfill-order-ids
 * Persist matches:     php artisan geidea:backfill-order-ids --backfill
 * Single reference:    php artisan geidea:backfill-order-ids --ref=17898530956aaefda74b924
 * Scope by date:       php artisan geidea:backfill-order-ids --from=2026-08-20 --to=2026-09-20
 */
class GeideaBackfillOrderIds extends Command
{
    protected $signature = 'geidea:backfill-order-ids
        {--backfill : Persist the recovered order_id (and raw response) to matching transactions}
        {--limit=0 : Max transactions to process (0 = all)}
        {--ref= : Process a single transaction_reference only}
        {--status=completed : Only this transaction status (use "all" to include pending/failed)}
        {--from= : Only transactions created on/after this date (e.g. 2026-08-20)}
        {--to= : Only transactions created on/before this date (e.g. 2026-09-20)}
        {--sleep=300 : Milliseconds to pause between Geidea calls (pacing to avoid rate limits)}';

    protected $description = 'Look up Geidea orders by merchantReferenceId for geidea transactions missing an order_id, and optionally backfill them.';

    /**
     * Geidea detailedStatus values that represent a real captured payment — the order is
     * genuine and its orderId should be recorded. "Refunded"/"PartiallyRefunded" are
     * included: the money WAS collected (then returned), so the order is still valid and
     * is exactly what the refund-reconciliation needs. "OrderFailed" etc. are excluded.
     */
    private const CAPTURED_STATUSES = ['Paid', 'Refunded', 'PartiallyRefunded'];

    public function handle(GeideaPayment $geidea): int
    {
        $query = Transaction::query()
            ->where('payment_gateway', 'geidea')
            ->where(function ($q) {
                $q->whereNull('order_id')->orWhere('order_id', '');
            });

        // Only paid/completed transactions can have a Geidea order worth recovering;
        // pending/failed ones never paid, so they'd always be NOT FOUND. Default to
        // 'completed'; pass --status=all to include every status.
        $status = (string) $this->option('status');
        if ($status !== '' && strtolower($status) !== 'all') {
            $query->where('status', $status);
        }

        if ($ref = $this->option('ref')) {
            $query->where('transaction_reference', $ref);
        }

        if ($from = $this->option('from')) {
            try {
                $query->whereDate('created_at', '>=', Carbon::parse($from)->toDateString());
            } catch (\Throwable $e) {
                $this->error("Invalid --from date: {$from}");

                return self::FAILURE;
            }
        }

        if ($to = $this->option('to')) {
            try {
                $query->whereDate('created_at', '<=', Carbon::parse($to)->toDateString());
            } catch (\Throwable $e) {
                $this->error("Invalid --to date: {$to}");

                return self::FAILURE;
            }
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $transactions = $query->orderBy('id')->get();

        if ($transactions->isEmpty()) {
            $this->info('No geidea transactions with a missing order_id.');

            return self::SUCCESS;
        }

        $backfill = (bool) $this->option('backfill');
        $sleepMs = max(0, (int) $this->option('sleep'));
        $this->warn($backfill
            ? 'BACKFILL MODE — matching order_ids will be written to the database.'
            : 'DRY RUN — no changes. Re-run with --backfill to persist matches.');

        $rows = [];
        $matched = 0;
        $notFound = 0;
        $needsReview = 0;
        $apiErrors = 0;
        $written = 0;

        $total = $transactions->count();
        $this->line("Querying Geidea for {$total} transaction(s) — one live call each (~2s), paced by --sleep.");
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%%  ~%remaining% left  (matched: %matched%)\n");
        $bar->setMessage('0', 'matched');
        $bar->start();

        foreach ($transactions as $index => $transaction) {
            if ($index > 0 && $sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            $bar->advance();
            $bar->setMessage((string) $matched, 'matched');

            $reference = (string) $transaction->transaction_reference;
            $orders = $geidea->fetchOrdersByReference($reference);

            if ($orders === null) {
                $apiErrors++;
                $rows[] = [$transaction->id, $reference, 'API ERROR', '', '', '', '', ''];

                continue;
            }

            // A reference can have several Geidea orders (a failed attempt + a successful
            // retry, or a later refund). Prefer a captured order (Paid/Refunded/…) that
            // matches the amount, then any captured order, then fall back to the first match
            // — so a stray OrderFailed never hides a real payment.
            $candidates = collect($orders)->filter(
                fn ($o) => (string) ($o['merchantReferenceId'] ?? '') === $reference
            );

            $isCaptured = fn ($o) => in_array((string) ($o['detailedStatus'] ?? ''), self::CAPTURED_STATUSES, true);

            $order = $candidates->first(fn ($o) => $isCaptured($o)
                    && abs((float) ($o['amount'] ?? 0) - (float) $transaction->amount) < 0.01)
                ?? $candidates->first($isCaptured)
                ?? $candidates->first();

            if (! $order) {
                $notFound++;
                $rows[] = [$transaction->id, $reference, 'NOT FOUND', '', '', '', '', ''];

                continue;
            }

            $orderId = (string) ($order['orderId'] ?? '');
            $detailedStatus = (string) ($order['detailedStatus'] ?? '');
            $statusOk = in_array($detailedStatus, self::CAPTURED_STATUSES, true);
            $amountOk = abs((float) ($order['amount'] ?? 0) - (float) $transaction->amount) < 0.01;
            $currencyOk = strcasecmp((string) ($order['currency'] ?? ''), (string) $transaction->currency) === 0;

            $isMatch = $orderId !== '' && $statusOk && $amountOk && $currencyOk;

            // Customer + amount Geidea holds for this order — shown so you can reconcile
            // against the local booking before backfilling.
            $gAmount = isset($order['amount']) ? number_format((float) $order['amount'], 2).' '.($order['currency'] ?? '') : '';
            $gCustomer = (string) ($order['customerName'] ?? '');
            $gEmail = (string) ($order['customerEmail'] ?? '');

            $result = 'MATCH';
            if (! $isMatch) {
                $reasons = [];
                if (! $statusOk) {
                    $reasons[] = 'status='.($detailedStatus ?: 'n/a');
                }
                if (! $amountOk) {
                    $reasons[] = 'amt✗';
                }
                if (! $currencyOk) {
                    $reasons[] = 'cur✗';
                }
                $result = 'REVIEW ('.implode(', ', $reasons).')';
            }

            $rows[] = [
                $transaction->id,
                $reference,
                $result,
                $orderId,
                $gAmount,
                $gCustomer,
                $gEmail,
                $detailedStatus,
            ];

            if (! $isMatch) {
                $needsReview++;

                continue;
            }

            $matched++;

            if ($backfill) {
                Transaction::withoutEvents(function () use ($transaction, $orderId, $order): void {
                    $transaction->order_id = $orderId;

                    if ($transaction->payment_gateway_response === null) {
                        $transaction->payment_gateway_response = json_encode($order);
                    }

                    $transaction->save();
                });
                $written++;
            }
        }

        $bar->setMessage((string) $matched, 'matched');
        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Tx ID', 'Reference', 'Result', 'Geidea orderId', 'Amount', 'Customer', 'Email', 'Status'],
            $rows
        );

        $this->newLine();
        $this->line("Processed: {$transactions->count()}");
        $this->line("  MATCH:        {$matched}".($backfill ? "  (written: {$written})" : ' (dry run — not written)'));
        $this->line("  NOT FOUND:    {$notFound}");
        $this->line("  REVIEW:       {$needsReview}  (found, but status/amount/currency disagree — do NOT auto-write)");
        $this->line("  API ERROR:    {$apiErrors}");

        if (! $backfill && $matched > 0) {
            $this->newLine();
            $this->info("Re-run with --backfill to write the {$matched} matched order_id(s).");
        }

        return self::SUCCESS;
    }
}
