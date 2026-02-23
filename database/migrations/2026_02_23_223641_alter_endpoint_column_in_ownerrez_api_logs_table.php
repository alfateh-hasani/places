<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ownerrez_api_logs', function (Blueprint $table) {
            // Drop the existing index before changing column type
            $table->dropIndex(['endpoint']);
        });

        Schema::table('ownerrez_api_logs', function (Blueprint $table) {
            // Change to text to support long URLs (e.g. with many property_ids)
            $table->text('endpoint')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ownerrez_api_logs', function (Blueprint $table) {
            $table->string('endpoint')->change();
        });

        Schema::table('ownerrez_api_logs', function (Blueprint $table) {
            $table->index('endpoint');
        });
    }
};
