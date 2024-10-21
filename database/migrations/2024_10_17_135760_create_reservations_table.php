<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('number_of_reservations')->index();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->date('check_in');
            $table->date('check_out');

            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount', 8, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->decimal('final_price', 10, 2);


            $table->integer('number_of_nights');
            $table->integer('number_of_adults');
            $table->integer('number_of_children');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('booking_source', ['website', 'mobile_app', 'other'])->default('website');

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
