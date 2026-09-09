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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'birth_year')) {
                $table->dropColumn('birth_year');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('calculated_pensions', function (Blueprint $table) {
            if (!Schema::hasColumn('calculated_pensions', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('recognized_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('recognized_documents', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('tax_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('tax_histories', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('birth_year')->nullable();
            $table->dropSoftDeletes();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('recognized_documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tax_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
