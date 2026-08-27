<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        DB::table('buildings')
            ->whereNotNull('ttlock_password')
            ->where('ttlock_password', '!=', '')
            ->get(['id', 'ttlock_password'])
            ->each(function ($building) {
                DB::table('buildings')
                    ->where('id', $building->id)
                    ->update(['ttlock_password' => Crypt::encryptString($building->ttlock_password)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('buildings')
            ->whereNotNull('ttlock_password')
            ->where('ttlock_password', '!=', '')
            ->get(['id', 'ttlock_password'])
            ->each(function ($building) {
                try {
                    $plaintext = Crypt::decryptString($building->ttlock_password);
                } catch (\Throwable $e) {
                    // Already plaintext (or undecryptable with the current APP_KEY) — leave as-is.
                    return;
                }

                DB::table('buildings')
                    ->where('id', $building->id)
                    ->update(['ttlock_password' => $plaintext]);
            });

    }
};
