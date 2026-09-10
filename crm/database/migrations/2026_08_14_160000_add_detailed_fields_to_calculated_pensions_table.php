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
        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->decimal('final_pension', 12, 2)->nullable()->after('user_id');
            $table->decimal('base_pension', 12, 2)->nullable()->after('final_pension');
            $table->decimal('zp_macroeconomic_average', 12, 2)->nullable()->after('base_pension');
            $table->decimal('kz_wage_coefficient', 8, 4)->nullable()->after('zp_macroeconomic_average');
            $table->decimal('ks_service_coefficient', 8, 4)->nullable()->after('kz_wage_coefficient');
            $table->integer('total_service_months')->nullable()->after('ks_service_coefficient');
            $table->string('pension_type')->nullable()->after('total_service_months');
            $table->string('disability_group')->nullable()->after('pension_type');
            $table->jsonb('input_parameters')->nullable()->after('disability_group');
            $table->jsonb('applied_benefits')->nullable()->after('input_parameters');
            $table->jsonb('calculation_logs')->nullable()->after('applied_benefits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->dropColumn([
                'final_pension',
                'base_pension',
                'zp_macroeconomic_average',
                'kz_wage_coefficient',
                'ks_service_coefficient',
                'total_service_months',
                'pension_type',
                'disability_group',
                'input_parameters',
                'applied_benefits',
                'calculation_logs',
            ]);
        });
    }
};
