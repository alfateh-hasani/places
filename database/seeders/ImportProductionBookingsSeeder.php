<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\CustomerSource;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One-shot importer for recent production data captured from the admin DataTables
 * JSON (used when direct DB/cPanel access isn't available). Handles, in order:
 *
 *   1. Customers     — inserts only those NOT already present (dedup by phone).
 *   2. Bookings      — matched to apartments by name; linked to a real customer
 *                      when the first name is unambiguous, else a placeholder.
 *   3. Transactions  — linked to bookings by booking number; dedup by reference.
 *
 * INPUT (save the `search` responses to these paths):
 *   storage/app/import/customers.json     (/admin/customer/search)
 *   storage/app/import/bookings.json      (/admin/booking/search)
 *   storage/app/import/transactions.json  (/admin/transaction/search)
 *
 * Safe & idempotent: bookings/customers/transactions insert WITHOUT model events
 * (no notifications, no OwnerRez sync) and re-running updates instead of duplicating.
 *
 * Run: php artisan db:seed --class=ImportProductionBookingsSeeder
 */
class ImportProductionBookingsSeeder extends Seeder
{
    // Booking row indices (/admin/booking/search)
    private const B_CUSTOMER = 0;

    private const B_STATUS = 1;

    private const B_BUILDING = 2;

    private const B_APARTMENT = 3;

    private const B_NUMBER = 4;

    private const B_OWNERREZ = 5;

    private const B_SOURCE = 6;

    private const B_CREATED = 7;

    private const B_CHECKIN = 8;

    private const B_CHECKOUT = 9;

    private const B_NIGHTS = 10;

    private const B_PRICE = 11;

    private const B_ACTIONS = 14;

    // Transaction row indices (/admin/transaction/search)
    private const T_BOOKING = 1;

    private const T_REFERENCE = 2;

    private const T_AMOUNT = 3;

    private const T_TYPE = 4;

    private const T_STATUS = 5;

    private const T_GATEWAY = 6;

    private const T_CREATED = 7;

    // Customer row indices (/admin/customer/search)
    private const C_FIRST = 0;

    private const C_LAST = 1;

    private const C_EMAIL = 2;

    private const C_PHONE = 3;

    private const C_CREATED = 7;

    /** Booking numbers to exclude entirely (and delete if already imported). */
    private const SKIP_BOOKINGS = ['00688326'];

    /** @var array<int, string> */
    private array $unmatchedApartments = [];

    /** @var array<string, array<int, Apartment>>|null Normalized name => apartments. */
    private ?array $apartmentIndex = null;

    /** @var array<int, string> Override phone linked, but first name differs from the booking. */
    private array $firstNameMismatches = [];

    /** @var array<int, string> Bookings whose customer could not be linked with confidence. */
    private array $ambiguousCustomers = [];

    /** @var array<string, string> booking number => customer phone (manual overrides). */
    private array $customerOverrides = [];

    private ?int $placeholderCustomerId = null;

    public function run(): void
    {
        $this->customerOverrides = $this->readMap(storage_path('app/import/booking-customer-map.json'));

        $this->deleteSkipped();

        $this->importCustomers();

        $bookings = $this->importBookings();
        if ($bookings === null) {
            return;
        }

        $this->importTransactions();

        if ($this->unmatchedApartments !== []) {
            $this->command->warn('Bookings skipped — apartment not found locally (fix the name, then re-run):');
            foreach ($this->unmatchedApartments as $line) {
                $this->command->line("  {$line}");
            }
        }

        if ($this->ambiguousCustomers !== []) {
            $this->command->newLine();
            $this->command->warn('REVIEW THESE — customer linked to the placeholder (first name not unique locally).');
            $this->command->line('Open each Show page, then add "booking number": "customer phone" to');
            $this->command->line('storage/app/import/booking-customer-map.json and re-run for exact links:');
            foreach ($this->ambiguousCustomers as $line) {
                $this->command->line("  {$line}");
            }
        }

        if ($this->firstNameMismatches !== []) {
            $this->command->newLine();
            $this->command->warn('NAME MISMATCH — override phone linked, but the customer first name differs from the booking (verify these):');
            foreach ($this->firstNameMismatches as $line) {
                $this->command->line("  {$line}");
            }
        }
    }

    /** Remove excluded bookings (and their transactions) if a prior run imported them. */
    private function deleteSkipped(): void
    {
        Booking::withoutEvents(function (): void {
            $bookings = Booking::whereIn('number_of_booking', self::SKIP_BOOKINGS)->get();
            foreach ($bookings as $booking) {
                Transaction::withoutEvents(fn () => Transaction::where('booking_id', $booking->id)->delete());
                $booking->delete();
                $this->command->info("Removed excluded booking {$booking->number_of_booking}.");
            }
        });
    }

    private function importCustomers(): void
    {
        $rows = $this->readJson(storage_path('app/import/customers.json'));
        if ($rows === null) {
            $this->command->warn('No customers.json — skipping customer import.');

            return;
        }

        $inserted = 0;
        $existing = 0;

        Customer::withoutEvents(function () use ($rows, &$inserted, &$existing): void {
            foreach ($rows as $row) {
                $phone = $this->text($row[self::C_PHONE] ?? '');
                if ($phone === '' || $phone === '-') {
                    continue;
                }

                if (Customer::where('phone', $phone)->exists()) {
                    $existing++;

                    continue;
                }

                $email = $this->text($row[self::C_EMAIL] ?? '');
                $created = $this->datetime($this->text($row[self::C_CREATED] ?? ''), $row[self::C_CREATED] ?? '') ?? now();

                Customer::create([
                    'first_name' => $this->text($row[self::C_FIRST] ?? ''),
                    'last_name' => $this->text($row[self::C_LAST] ?? ''),
                    'email' => ($email !== '' && $email !== '-') ? strtolower($email) : null,
                    'phone' => $phone,
                    'source' => CustomerSource::Local,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]);
                $inserted++;
            }
        });

        $this->command->info("Customers: inserted {$inserted}, already present {$existing}.");
    }

    /**
     * @return array<string, Booking>|null  booking number => model, or null if input missing
     */
    private function importBookings(): ?array
    {
        $rows = $this->readJson(storage_path('app/import/bookings.json'));
        if ($rows === null) {
            $this->command->error('Missing storage/app/import/bookings.json.');

            return null;
        }

        $imported = 0;
        $skipped = 0;
        $byNumber = [];

        Booking::withoutEvents(function () use ($rows, &$imported, &$skipped, &$byNumber): void {
            foreach ($rows as $row) {
                $number = $this->text($row[self::B_NUMBER] ?? '');

                if (in_array($number, self::SKIP_BOOKINGS, true)) {
                    continue;
                }

                $aptName = $this->text($row[self::B_APARTMENT] ?? '');
                $buildingName = $this->text($row[self::B_BUILDING] ?? '');

                $apartment = $this->findApartment($aptName, $buildingName);
                if (! $apartment) {
                    $this->unmatchedApartments[] = "{$number}: {$buildingName} / {$aptName}";
                    $skipped++;

                    continue;
                }

                $checkIn = $this->date($this->text($row[self::B_CHECKIN] ?? ''));
                $checkOut = $this->date($this->text($row[self::B_CHECKOUT] ?? ''));
                if (! $checkIn || ! $checkOut) {
                    $skipped++;

                    continue;
                }

                $customerName = $this->text($row[self::B_CUSTOMER] ?? '');
                [$customer, $how] = $this->resolveCustomer($customerName, $number);
                if ($how === 'placeholder') {
                    $showId = preg_match('#/booking/(\d+)/show#', (string) ($row[self::B_ACTIONS] ?? ''), $m) ? $m[1] : '?';
                    $this->ambiguousCustomers[] = "{$number}  ({$customerName})  https://dyafa.sa/admin/booking/{$showId}/show";
                }

                $nights = (int) $this->text($row[self::B_NIGHTS] ?? '') ?: $checkIn->diffInDays($checkOut);
                $price = $this->money($this->text($row[self::B_PRICE] ?? ''));
                $statusLabel = $this->text($row[self::B_STATUS] ?? '');
                $status = $this->mapBookingStatus($statusLabel);
                $ownerrez = $this->text($row[self::B_OWNERREZ] ?? '');
                $createdAt = $this->datetime($this->text($row[self::B_CREATED] ?? '')) ?? now();
                $building = Building::find($apartment->building_id);

                // Direct dashboard bookings show "حجز مباشر"; web bookings show "ويب".
                $bookingSource = str_contains($this->text($row[self::B_SOURCE] ?? ''), 'مباشر') ? 'dashboard' : 'web';

                // Store the customer's FULL name (first + last) so the booking show page
                // isn't limited to the first-name-only value from the export.
                $fullName = $how === 'placeholder'
                    ? ($customerName ?: $customer->first_name)
                    : trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));

                $attributes = [
                    'apartment_id' => $apartment->id,
                    'customer_id' => $customer->id,
                    'customer_full_name' => $fullName !== '' ? $fullName : ($customerName ?: $customer->first_name),
                    'customer_email' => $customer->email,
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'number_of_nights' => $nights,
                    'adults_count' => 1,
                    'children_count' => 0,
                    'status' => $status,
                    'payment_status' => $this->paymentStatusFromLabel($statusLabel),
                    'payment_method_code' => $bookingSource === 'dashboard' ? 'bank_transfer' : null,
                    'total_price' => $price,
                    'final_price' => $price,
                    'discount' => 0,
                    'one_night_price' => $nights > 0 ? round($price / $nights, 2) : $price,
                    'tax' => round($price * 15 / 115, 2),
                    'booking_source' => $bookingSource,
                    'ownerrez_booking_id' => ctype_digit($ownerrez) ? $ownerrez : null,
                    'is_airbnb_booking' => false,
                    'check_in_time' => $building?->check_in_time,
                    'check_out_time' => $building?->check_out_time,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $booking = Booking::where('number_of_booking', $number)->first();
                if ($booking) {
                    $booking->forceFill($attributes)->save();
                } else {
                    $booking = Booking::create($attributes + [
                        'uuid' => (string) Str::uuid(),
                        'number_of_booking' => $number,
                    ]);
                }

                $byNumber[$number] = $booking;
                $imported++;
            }
        });

        $this->command->info("Bookings: imported/updated {$imported}, skipped {$skipped}.");

        return $byNumber;
    }

    private function importTransactions(): void
    {
        $rows = $this->readJson(storage_path('app/import/transactions.json'));
        if ($rows === null) {
            $this->command->warn('No transactions.json — skipping transaction import.');

            return;
        }

        $imported = 0;
        $unlinked = 0;

        Transaction::withoutEvents(function () use ($rows, &$imported, &$unlinked): void {
            foreach ($rows as $row) {
                $number = $this->text($row[self::T_BOOKING] ?? '');
                $reference = $this->text($row[self::T_REFERENCE] ?? '');

                if ($number === '' || $number === '-' || $reference === '' || in_array($number, self::SKIP_BOOKINGS, true)) {
                    $unlinked++;

                    continue;
                }

                $booking = Booking::where('number_of_booking', $number)->first();
                if (! $booking) {
                    $unlinked++;

                    continue;
                }

                $created = $this->datetime($this->text($row[self::T_CREATED] ?? '')) ?? $booking->created_at ?? now();
                $status = $this->mapTransactionStatus($this->text($row[self::T_STATUS] ?? ''));

                $transaction = Transaction::updateOrCreate(
                    ['transaction_reference' => $reference],
                    [
                        'customer_id' => $booking->customer_id,
                        'apartment_id' => $booking->apartment_id,
                        'booking_id' => $booking->id,
                        'amount' => $this->money($this->text($row[self::T_AMOUNT] ?? '')),
                        'currency' => 'SAR',
                        'type' => str_contains($this->text($row[self::T_TYPE] ?? ''), 'سحب') ? 'withdrawal' : 'deposit',
                        'status' => $status,
                        'payment_gateway' => str_contains($this->text($row[self::T_GATEWAY] ?? ''), 'bank_transfer') ? 'bank_transfer' : 'geidea',
                        'platform' => 'web',
                        'created_at' => $created,
                        'updated_at' => $created,
                    ],
                );

                if ($status === 'completed' && ! $booking->transaction_id) {
                    $booking->forceFill(['transaction_id' => $transaction->id])->save();
                }

                $imported++;
            }
        });

        $this->command->info("Transactions: imported/updated {$imported}, unlinked/skipped {$unlinked}.");
    }

    /**
     * Resolve the booking's customer, most-trusted source first:
     *   1. manual override (booking number => phone) from booking-customer-map.json
     *   2. unique first-name match in the local DB
     *   3. shared placeholder (real name is still kept on the booking)
     *
     * @return array{0: Customer, 1: string}  [customer, 'override'|'unique'|'placeholder']
     */
    private function resolveCustomer(string $firstName, string $number): array
    {
        $phone = trim($this->customerOverrides[$number] ?? '');
        if ($phone !== '') {
            // The map may store the number without the leading "+" — try both.
            $byPhone = Customer::whereIn('phone', array_unique([$phone, '+'.ltrim($phone, '+')]))->first();
            if ($byPhone) {
                if ($this->normalize(mb_strtolower((string) $byPhone->first_name)) !== $this->normalize(mb_strtolower($firstName))) {
                    $this->firstNameMismatches[] = "{$number}: booking '{$firstName}' vs customer '{$byPhone->first_name}' ({$phone})";
                }

                return [$byPhone, 'override'];
            }
            $this->command->warn("Override phone {$phone} for booking {$number} not found locally — falling back.");
        }

        if ($firstName !== '') {
            $matches = Customer::where('first_name', $firstName)->limit(2)->get();
            if ($matches->count() === 1) {
                return [$matches->first(), 'unique'];
            }
        }

        return [$this->placeholderCustomer(), 'placeholder'];
    }

    private function placeholderCustomer(): Customer
    {
        if ($this->placeholderCustomerId !== null) {
            return Customer::find($this->placeholderCustomerId);
        }

        $customer = Customer::withoutEvents(fn (): Customer => Customer::firstOrCreate(
            ['phone' => 'imported-production'],
            ['first_name' => 'Imported', 'last_name' => '(production)', 'email' => null, 'source' => CustomerSource::Local],
        ));

        $this->placeholderCustomerId = $customer->id;

        return $customer;
    }

    private function findApartment(string $aptName, string $buildingName): ?Apartment
    {
        $candidates = $this->apartmentIndex()[$this->normalize($aptName)] ?? [];

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        if (count($candidates) > 1 && $buildingName !== '') {
            $bKey = $this->normalize($buildingName);
            foreach ($candidates as $apt) {
                $b = Building::find($apt->building_id);
                if ($b && ($this->normalize((string) $b->name_ar) === $bKey || $this->normalize((string) $b->name_en) === $bKey)) {
                    return $apt;
                }
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * Apartments indexed by whitespace-normalized name (ar + en), so double spaces
     * or trailing spaces in the export still match the DB.
     *
     * @return array<string, array<int, Apartment>>
     */
    private function apartmentIndex(): array
    {
        if ($this->apartmentIndex !== null) {
            return $this->apartmentIndex;
        }

        $index = [];
        foreach (Apartment::all() as $apt) {
            foreach ([$apt->name_ar, $apt->name_en] as $name) {
                $key = $this->normalize((string) $name);
                if ($key !== '') {
                    $index[$key][] = $apt;
                }
            }
        }

        return $this->apartmentIndex = $index;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function mapBookingStatus(string $label): string
    {
        return match (true) {
            str_contains($label, 'ملغي من العميل') => BookingStatus::CancellationRequested->value,
            str_contains($label, 'مؤكد') => BookingStatus::Approved->value,
            str_contains($label, 'ملغي') => BookingStatus::Canceled->value,
            str_contains($label, 'قيد الانتظار') => BookingStatus::Pending->value,
            // Finished (منتهي) and rejected (مرفوض) are imported as canceled per request.
            str_contains($label, 'مرفوض') => BookingStatus::Canceled->value,
            str_contains($label, 'منتهي') => BookingStatus::Canceled->value,
            str_contains($label, 'محجوز') => BookingStatus::Booked->value,
            default => BookingStatus::Approved->value,
        };
    }

    /**
     * Payment status derived from the ORIGINAL production status label — kept
     * independent of the booking-status remap (finished/rejected → canceled),
     * so a rejected booking stays unpaid ('failed') even though it's stored as canceled.
     */
    private function paymentStatusFromLabel(string $label): string
    {
        return match (true) {
            str_contains($label, 'مرفوض') => 'failed',        // rejected — never paid
            str_contains($label, 'قيد الانتظار') => 'pending', // pending — awaiting payment
            str_contains($label, 'محجوز') => 'pending',        // booked/imported hold
            default => 'paid',                                  // confirmed, finished, canceled
        };
    }

    private function mapTransactionStatus(string $label): string
    {
        return match (true) {
            str_contains($label, 'مكتمل') => 'completed',
            str_contains($label, 'فشل') => 'failed',
            default => 'pending',
        };
    }

    /**
     * @return array<int, array<int, string>>|null
     */
    private function readJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return $decoded['data'] ?? null;
    }

    /**
     * Optional { "bookingNumber": "customerPhone" } overrides for exact linking.
     *
     * @return array<string, string>
     */
    private function readMap(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? array_map('strval', $decoded) : [];
    }

    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    }

    private function money(string $text): float
    {
        return preg_match('/([\d,]+\.?\d*)/', $text, $m) ? (float) str_replace(',', '', $m[1]) : 0.0;
    }

    private function date(string $text): ?Carbon
    {
        return preg_match('/\d{4}-\d{2}-\d{2}/', $text) ? Carbon::parse($text)->startOfDay() : null;
    }

    /**
     * Parse a datetime from plain text, or from a `data-order="Y-m-d H:i:s"` attribute
     * if the raw cell HTML is provided (used for the customers "created" column).
     */
    private function datetime(string $text, string $rawHtml = ''): ?Carbon
    {
        if ($rawHtml !== '' && preg_match('/data-order="([^"]+)"/', $rawHtml, $m)) {
            return Carbon::parse($m[1]);
        }

        if (preg_match('/\d{4}-\d{2}-\d{2}/', $text)) {
            return Carbon::parse($text);
        }

        if (preg_match('/\d{1,2}\s+\w+\s+\d{4}/', $text)) {
            return Carbon::parse($text);
        }

        return null;
    }
}
