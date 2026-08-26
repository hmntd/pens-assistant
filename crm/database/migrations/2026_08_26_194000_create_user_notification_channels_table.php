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
        Schema::create('user_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();

            // Channels (All disabled by default)
            $table->boolean('email_enabled')->default(false);

            $table->boolean('telegram_enabled')->default(false);
            $table->string('telegram_chat_id')->nullable();

            $table->boolean('sms_enabled')->default(false);
            $table->string('phone_number')->nullable();

            // Event Notification Preferences
            $table->boolean('notify_calc_completed')->default(true);
            $table->boolean('notify_document_processed')->default(true);
            $table->boolean('notify_system_alerts')->default(true);
            $table->boolean('notify_pension_updates')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_channels');
    }
};
