<?php

use App\Models\TaxHistory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $records = TaxHistory::whereNull('monthly_breakdown')->get();

        foreach ($records as $th) {
            $monthsWorked = min(12, max(1, (int) ($th->months_worked ?: 12)));
            $monthlyAvg = (float) $th->annual_income / $monthsWorked;
            $breakdown = [];
            for ($m = 1; $m <= 12; $m++) {
                $breakdown[$m] = $m <= $monthsWorked ? round($monthlyAvg, 2) : 0.0;
            }

            $th->update(['monthly_breakdown' => $breakdown]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
