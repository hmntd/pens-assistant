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
        if (Schema::hasTable('audit_logs')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->string('entity_id', 255)->nullable()->change();
                });
            } else {
                DB::statement('ALTER TABLE audit_logs ALTER COLUMN entity_id TYPE VARCHAR(255) USING entity_id::varchar');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->unsignedBigInteger('entity_id')->nullable()->change();
                });
            } else {
                DB::statement('ALTER TABLE audit_logs ALTER COLUMN entity_id TYPE BIGINT USING entity_id::bigint');
            }
        }
    }
};
