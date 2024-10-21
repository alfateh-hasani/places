<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('apartment_labels')) {
            Schema::create('apartment_labels', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('description_ar');
                $table->string('description_en');
                $table->timestamps();
            });
        }
        //label_id and apartment_id many to many

        if (!Schema::hasTable('apartment_label_apartment')) {
            Schema::create('apartment_label_apartment', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('label_id');
                $table->unsignedBigInteger('apartment_id');
                $table->foreign('label_id')->references('id')->on('apartment_labels')->onDelete('cascade');
                $table->foreign('apartment_id')->references('id')->on('apartments')->onDelete('cascade');
                $table->timestamps();
            });
        }


    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_labels');
        Schema::dropIfExists('apartment_label_apartment');
    }
};
