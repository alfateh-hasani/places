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
        Schema::create('sciener_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token')->unique();
            $table->string('refresh_token')->nullable();
            $table->unsignedBigInteger('uid')->nullable();
            $table->unsignedBigInteger('openid')->nullable();
            $table->string('scope')->nullable();
            $table->string('token_type')->nullable();
            $table->integer('expires_in')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sciener_tokens');
    }
};
