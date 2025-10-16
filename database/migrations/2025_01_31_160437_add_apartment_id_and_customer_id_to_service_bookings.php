<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('apartment_id')->after('service_id');
            $table->unsignedBigInteger('customer_id')->after('apartment_id');

            // إضافة مفاتيح خارجية لضمان سلامة البيانات
            $table->foreign('apartment_id')->references('id')->on('apartments')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropForeign(['apartment_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn('apartment_id');
            $table->dropColumn('customer_id');
        });
    }
};
