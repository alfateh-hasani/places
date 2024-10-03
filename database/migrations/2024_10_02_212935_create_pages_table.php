<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar',191);
            $table->string('name_en',191);
            $table->text('content_ar');
            $table->text('content_en');
            $table->string('slug')->unique();
            $table->string('template')->default('default');
            $table->string('seo_title_ar',191);
            $table->string('seo_title_en',191);
            $table->string('seo_description_en',191);
            $table->string('seo_description_ar',191);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
