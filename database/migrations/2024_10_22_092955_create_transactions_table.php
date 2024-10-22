<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreignId('apartment_id');
            $table->foreign('apartment_id')->references('id')->on('apartments');
            $table->text('transaction_reference');
            $table->float('amount');
            $table->string('currency');
            $table->enum('type', ['deposit', 'withdrawal'])->default('deposit');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('payment_gateway',['tap', 'tabby', 'tamara','qitaf'])->default('tap');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
