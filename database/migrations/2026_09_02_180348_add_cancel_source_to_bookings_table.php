<?php

use App\Enums\CancelSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records who initiated a cancellation (customer self-service vs. staff) so the
     * shared "cancellation requested" booking status can be labelled honestly.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->enum('cancel_source', [CancelSource::Customer->value, CancelSource::Staff->value])
                ->nullable()
                ->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('cancel_source');
        });
    }
};
