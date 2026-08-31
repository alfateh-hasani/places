<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_notifications', function (Blueprint $table): void {
            $table->id();
            // Polymorphic recipient: App\Models\User (staff/admin) or App\Models\Customer (web user).
            // Kept entirely separate from the legacy mobile `notifications` table.
            $table->morphs('notifiable');
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Hot path: unread/recent notifications for a given recipient.
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'web_notifications_recipient_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_notifications');
    }
};
