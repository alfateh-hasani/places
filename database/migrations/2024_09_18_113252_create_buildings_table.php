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
        Schema::disableForeignKeyConstraints();

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->text('name_ar')->charset('utf8mb4');
            $table->text('name_en')->charset('utf8mb4');
            $table->text('address')->nullable()->charset('utf8mb4');
            $table->foreignId('city_id')->nullable()->constrained()->index();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
