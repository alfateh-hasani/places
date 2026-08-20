<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Creates the Refunds-page permissions. Role assignment is done manually
 * from the admin Roles/Permissions page.
 *
 * Run: php artisan db:seed --class=RefundPermissionSeeder
 */
class RefundPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['refund.list', 'refund.retry'] as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'backpack',
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
