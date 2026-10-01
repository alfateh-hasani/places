<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Project-wide permissions seeder.
 *
 * The single source of truth for all Backpack permissions. Idempotent (firstOrCreate) —
 * safe to run any time; add new permissions to the $permissions list as features grow.
 * It ONLY creates the permissions; assigning them to roles is done manually from the
 * admin Roles/Permissions page.
 *
 * Run: php artisan db:seed --class=PermissionSeeder
 */
class PermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        // Cancellation / refund requests (CanceledBookings + Refunds tracker)
        'booking.list',
        'booking.changeStatus',

        // Direct (manual bank-transfer) booking from the dashboard
        'direct-booking.create',
        'refund.list',
        'refund.retry',

        // Date-change requests
        'date_change.list',
        'date_change.manage',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'backpack',
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
