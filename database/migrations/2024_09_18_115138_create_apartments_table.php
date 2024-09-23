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

        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->text('name_ar')->charset('utf8mb4');
            $table->text('name_en')->charset('utf8mb4');
            $table->foreignId('building_id')->nullable()->constrained()->index();
            $table->longText('description_ar')->nullable()->charset('utf8mb4');
            $table->longText('description_en')->nullable()->charset('utf8mb4');
            $table->bigInteger('num_rooms')->index();
            $table->bigInteger('num_beds')->index() ;
            $table->decimal('area', 5, 2);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->boolean('is_active')->default(true);
            $table->bigInteger('smart_lock_id')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
