<?php

use App\Enums\CustomerSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('source')->default(CustomerSource::Local->value)->after('ownerrez_guest_id');
        });

        // Backfill: registerUser() (API + web) always requires a non-null unique email,
        // so any existing row with a null email can only have come from the OwnerRez
        // auto-create path (findOrCreateCustomerFromOwnerRez), which explicitly sets
        // email to null. Requiring ownerrez_guest_id too keeps this conservative.
        DB::table('customers')
            ->whereNull('email')
            ->whereNotNull('ownerrez_guest_id')
            ->update(['source' => CustomerSource::OwnerRez->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
