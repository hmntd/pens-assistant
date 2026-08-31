<?php

namespace Tests\Feature;

use App\Models\CalculatedPension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PensionCalculationPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_own_pension_calculation_pdf(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Олексій',
            'last_name' => 'Коваленко',
            'gender' => 'MALE',
            'date_of_birth' => '1965-04-12',
            'target_retirement_year' => 2025,
        ]);

        $calculation = CalculatedPension::create([
            'user_id' => $user->id,
            'pension_type' => 'OLD_AGE',
            'total_service_months' => 420,
            'kz_wage_coefficient' => 1.8542,
            'zp_macroeconomic_average' => 16500.00,
            'ks_service_coefficient' => 0.3500,
            'base_pension' => 10707.00,
            'final_pension' => 10707.00,
            'estimated_monthly_pension' => 10707.00,
            'total_accumulated_capital' => 0.00,
            'calculation_logs' => ['Stage 1: Base pension computed'],
        ]);

        $responseUk = $this->actingAs($user)->get('/pension-calculations/' . $calculation->id . '/pdf?lang=uk');
        $responseUk->assertStatus(200);
        $responseUk->assertHeader('Content-Type', 'application/pdf');

        $responseEn = $this->actingAs($user)->get('/pension-calculations/' . $calculation->id . '/pdf?lang=en');
        $responseEn->assertStatus(200);
        $responseEn->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_user_cannot_download_another_users_pdf(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $calculation = CalculatedPension::create([
            'user_id' => $user1->id,
            'pension_type' => 'OLD_AGE',
            'total_service_months' => 360,
            'kz_wage_coefficient' => 1.2000,
            'zp_macroeconomic_average' => 16500.00,
            'ks_service_coefficient' => 0.3000,
            'base_pension' => 5940.00,
            'final_pension' => 5940.00,
            'estimated_monthly_pension' => 5940.00,
            'total_accumulated_capital' => 0.00,
        ]);

        $response = $this->actingAs($user2)->get('/pension-calculations/' . $calculation->id . '/pdf');

        $response->assertStatus(403);
    }

    public function test_admin_can_download_any_pension_calculation_pdf(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $calculation = CalculatedPension::create([
            'user_id' => $user->id,
            'pension_type' => 'OLD_AGE',
            'total_service_months' => 300,
            'kz_wage_coefficient' => 1.5000,
            'zp_macroeconomic_average' => 16500.00,
            'ks_service_coefficient' => 0.2500,
            'base_pension' => 6187.50,
            'final_pension' => 6187.50,
            'estimated_monthly_pension' => 6187.50,
            'total_accumulated_capital' => 0.00,
        ]);

        $response = $this->actingAs($admin)->get('/admin/pension-calculations/' . $calculation->id . '/pdf?lang=en');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
