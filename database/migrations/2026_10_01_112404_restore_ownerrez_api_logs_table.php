<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The production database was moved to a new server without this table although its
     * migrations are recorded as run, so every OwnerRez API call failed while logging.
     * Recreate it with its final schema (endpoint as text, see 2026_02_23) when missing.
     */
    public function up(): void
    {
        if (Schema::hasTable('ownerrez_api_logs')) {
            return;
        }

        Schema::create('ownerrez_api_logs', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint');
            $table->enum('method', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at');

            $table->index('created_at');
        });
    }

    /**
     * Restores a table owned by earlier migrations, so rolling this back leaves it in place.
     */
    public function down(): void
    {
        //
    }
};
