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
        Schema::create('onboarding', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar')->comment('العنوان بالعربية');
            $table->string('title_en')->comment('العنوان بالإنجليزية');
            $table->text('description_ar')->nullable()->comment('الوصف بالعربية');
            $table->text('description_en')->nullable()->comment('الوصف بالإنجليزية');
            $table->integer('order')->default(0)->comment('الترتيب');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding');
    }
};
