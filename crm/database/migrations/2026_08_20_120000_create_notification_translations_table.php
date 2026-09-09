<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_translations', function (Blueprint $table) {
            $table->id();
            $table->text('uk');
            $table->text('en');
            $table->timestamps();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('notification_translation_id')
                ->nullable()
                ->after('user_id')
                ->constrained('notification_translations')
                ->nullOnDelete();
        });

        // Migrate existing records in notifications table
        $existingNotifications = DB::table('notifications')->get();
        foreach ($existingNotifications as $notif) {
            $translationId = DB::table('notification_translations')->insertGetId([
                'uk' => $notif->message ?? '',
                'en' => $notif->message ?? '',
                'created_at' => $notif->created_at ?? now(),
                'updated_at' => $notif->updated_at ?? now(),
            ]);

            DB::table('notifications')
                ->where('id', $notif->id)
                ->update(['notification_translation_id' => $translationId]);
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->text('message')->nullable();
        });

        $notifications = DB::table('notifications')->get();
        foreach ($notifications as $notif) {
            if ($notif->notification_translation_id) {
                /** @var stdClass|null $trans */
                $trans = DB::table('notification_translations')->find($notif->notification_translation_id);
                if ($trans && property_exists($trans, 'uk')) {
                    DB::table('notifications')
                        ->where('id', $notif->id)
                        ->update(['message' => $trans->uk]);
                }
            }
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['notification_translation_id']);
            $table->dropColumn('notification_translation_id');
        });

        Schema::dropIfExists('notification_translations');
    }
};
