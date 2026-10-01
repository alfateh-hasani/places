<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('seo_title_ar')->nullable()->after('address_en');
            $table->string('seo_title_en')->nullable()->after('seo_title_ar');
            $table->text('seo_description_ar')->nullable()->after('seo_title_en');
            $table->text('seo_description_en')->nullable()->after('seo_description_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en']);
        });
    }
};
