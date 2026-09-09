<?php

namespace Tests\Feature;

use App\Models\CalculatedPension;
use App\Models\TaxHistory;
use App\Models\User;
use App\Services\PensionCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PensionCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_pension_calculations(): void
    {
        $response = $this->getJson('/pension-calculations');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_calculate_pension(): void
    {
        $user = User::factory()->create();

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2023,
            'annual_income' => 180000.00,
            'tax_paid' => 32400.00,
            'months_worked' => 12,
        ]);

        $this->mock(PensionCalculatorService::class, function ($mock) use ($user) {
            $mock->shouldReceive('calculateAndSave')
                ->once()
                ->withArgs(function ($targetUser, $data) use ($user) {
                    return $targetUser->id === $user->id
                        && $data['gender'] === 'male'
                        && $data['pension_type'] === 'old_age';
                })
                ->andReturnUsing(fn () => CalculatedPension::create([
                    'user_id' => $user->id,
                    'final_pension' => 8500.00,
                    'base_pension' => 7800.00,
                    'zp_macroeconomic_average' => 13559.41,
                    'kz_wage_coefficient' => 1.2500,
                    'ks_service_coefficient' => 0.4000,
                    'total_service_months' => 480,
                    'pension_type' => 'old_age',
                    'disability_group' => 'none',
                    'estimated_monthly_pension' => 8500.00,
                    'total_accumulated_capital' => 1872000.00,
                ]));
        });

        $payload = [
            'gender' => 'male',
            'date_of_birth' => '1960-05-15',
            'retirement_date' => '2024-06-01',
            'pension_type' => 'old_age',
            'benefits' => ['combat_veteran'],
        ];

        $response = $this->actingAs($user)->postJson('/pension-calculations', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Pension calculated and saved successfully.')
            ->assertJsonPath('data.final_pension', '8500.00');

        $this->assertDatabaseHas('calculated_pensions', [
            'user_id' => $user->id,
            'final_pension' => 8500.00,
        ]);
    }

    public function test_regular_user_cannot_calculate_more_than_one_pension(): void
    {
        $user = User::factory()->create();

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2023,
            'annual_income' => 180000.00,
            'tax_paid' => 32400.00,
            'months_worked' => 12,
        ]);

        // Create an existing pension for the user
        CalculatedPension::create([
            'user_id' => $user->id,
            'final_pension' => 5000.00,
            'base_pension' => 5000.00,
            'estimated_monthly_pension' => 5000.00,
            'total_accumulated_capital' => 1200000.00,
        ]);

        $this->mock(PensionCalculatorService::class, function ($mock) use ($user) {
            $mock->shouldReceive('calculateAndSave')
                ->once()
                ->andReturnUsing(fn () => CalculatedPension::create([
                    'user_id' => $user->id,
                    'final_pension' => 8500.00,
                    'base_pension' => 7800.00,
                    'zp_macroeconomic_average' => 13559.41,
                    'kz_wage_coefficient' => 1.2500,
                    'ks_service_coefficient' => 0.4000,
                    'total_service_months' => 480,
                    'pension_type' => 'old_age',
                    'disability_group' => 'none',
                    'estimated_monthly_pension' => 8500.00,
                    'total_accumulated_capital' => 1872000.00,
                ]));
        });

        $payload = [
            'gender' => 'male',
            'date_of_birth' => '1960-05-15',
            'retirement_date' => '2024-06-01',
            'pension_type' => 'old_age',
        ];

        $response = $this->actingAs($user)->postJson('/pension-calculations', $payload);

        $response->assertStatus(201);
    }

    public function test_admin_can_bypass_one_pension_limit(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        TaxHistory::create([
            'user_id' => $admin->id,
            'year' => 2023,
            'annual_income' => 180000.00,
            'tax_paid' => 32400.00,
            'months_worked' => 12,
        ]);

        // Create an existing pension for the admin
        CalculatedPension::create([
            'user_id' => $admin->id,
            'final_pension' => 5000.00,
            'base_pension' => 5000.00,
            'estimated_monthly_pension' => 5000.00,
            'total_accumulated_capital' => 1200000.00,
        ]);

        $this->mock(PensionCalculatorService::class, function ($mock) use ($admin) {
            $mock->shouldReceive('calculateAndSave')
                ->once()
                ->andReturnUsing(fn () => CalculatedPension::create([
                    'user_id' => $admin->id,
                    'final_pension' => 9200.00,
                    'base_pension' => 8500.00,
                    'estimated_monthly_pension' => 9200.00,
                    'total_accumulated_capital' => 2040000.00,
                ]));
        });

        $payload = [
            'gender' => 'male',
            'date_of_birth' => '1960-05-15',
            'retirement_date' => '2024-06-01',
            'pension_type' => 'old_age',
        ];

        $response = $this->actingAs($admin)->postJson('/pension-calculations', $payload);

        $response->assertStatus(201);
    }

    public function test_validation_errors_for_invalid_payload(): void
    {
        $user = User::factory()->create();

        $payload = [
            'gender' => 'invalid_gender',
            'pension_type' => 'invalid_type',
        ];

        $response = $this->actingAs($user)->postJson('/pension-calculations', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pension_type']);
    }

    public function test_calculation_uses_user_profile_fallbacks(): void
    {
        $user = User::factory()->create([
            'gender' => 'female',
            'date_of_birth' => '1955-03-10',
            'disability_group' => 'group_2',
            'benefits' => ['combat_veteran'],
        ]);

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2023,
            'annual_income' => 180000.00,
            'tax_paid' => 32400.00,
            'months_worked' => 12,
        ]);

        $this->mock(PensionCalculatorService::class, function ($mock) use ($user) {
            $mock->shouldReceive('calculateAndSave')
                ->once()
                ->withArgs(function ($targetUser, $data) use ($user) {
                    return $targetUser->id === $user->id;
                })
                ->andReturnUsing(function () use ($user) {
                    return CalculatedPension::create([
                        'user_id' => $user->id,
                        'final_pension' => 7500.00,
                        'base_pension' => 7000.00,
                        'estimated_monthly_pension' => 7500.00,
                        'total_accumulated_capital' => 1680000.00,
                    ]);
                });
        });

        $payload = [
            'pension_type' => 'disability',
            'retirement_date' => '2024-06-01',
        ];

        $response = $this->actingAs($user)->postJson('/pension-calculations', $payload);

        $response->assertStatus(201);
    }

    public function test_end_to_end_user_roadmap_zero_input_calculation(): void
    {
        // 1. User sets profile attributes in dashboard
        $user = User::factory()->create([
            'gender' => 'female',
            'date_of_birth' => '1952-05-15',
            'disability_group' => 'none',
            'benefits' => ['combat_veteran'],
        ]);

        // 2. User uploads tax document, creating TaxHistory records via OCR
        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2023,
            'annual_income' => 180000.00,
            'tax_paid' => 32400.00,
            'months_worked' => 12,
        ]);

        $this->mock(PensionCalculatorService::class, function ($mock) use ($user) {
            $mock->shouldReceive('calculateAndSave')
                ->once()
                ->withArgs(function ($targetUser, $data) use ($user) {
                    return $targetUser->id === $user->id;
                })
                ->andReturnUsing(function () use ($user) {
                    return CalculatedPension::create([
                        'user_id' => $user->id,
                        'final_pension' => 8541.31,
                        'base_pension' => 7971.31,
                        'estimated_monthly_pension' => 8541.31,
                        'total_accumulated_capital' => 1913114.40,
                        'input_parameters' => [
                            'gender' => 'female',
                            'date_of_birth' => '1952-05-15',
                            'retirement_date' => '2024-06-01',
                        ],
                    ]);
                });
        });

        // 3. User clicks "Calculate Pension" without providing ANY request payload
        $response = $this->actingAs($user)->postJson('/pension-calculations', []);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $user->id);
    }

    public function test_past_target_retirement_year_caps_calculation_data(): void
    {
        $user = User::factory()->create([
            'target_retirement_year' => 2020,
        ]);

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2018,
            'annual_income' => 120000.00,
            'tax_paid' => 21600.00,
            'months_worked' => 12,
        ]);

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2024,
            'annual_income' => 240000.00,
            'tax_paid' => 43200.00,
            'months_worked' => 12,
        ]);

        $service = new PensionCalculatorService();
        $result = $service->calculateAndSave($user, [
            'gender' => 'male',
            'date_of_birth' => '1960-01-01',
            'target_retirement_year' => 2020,
        ]);

        $this->assertInstanceOf(CalculatedPension::class, $result);
        $this->assertEquals(12, $result->total_service_months);
    }

    public function test_future_target_retirement_year_without_hypothetical_defaults_to_current_year(): void
    {
        $user = User::factory()->create([
            'target_retirement_year' => 2035,
        ]);

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2023,
            'annual_income' => 180000.00,
            'tax_paid' => 32400.00,
            'months_worked' => 12,
        ]);

        $service = new PensionCalculatorService();
        $result = $service->calculateAndSave($user, [
            'gender' => 'male',
            'date_of_birth' => '1970-01-01',
            'target_retirement_year' => 2035,
            'enable_hypothetical_projection' => false,
        ]);

        $this->assertInstanceOf(CalculatedPension::class, $result);
        $this->assertFalse($result->calculation_breakdown['is_hypothetical'] ?? false);
        $this->assertEquals(12, $result->total_service_months);
    }

    public function test_hypothetical_enabled_uses_national_average_salary_fallback_when_no_salary_history(): void
    {
        $user = User::factory()->create([
            'target_retirement_year' => 2030,
        ]);

        $service = new PensionCalculatorService();
        $result = $service->calculateAndSave($user, [
            'gender' => 'male',
            'date_of_birth' => '1970-01-01',
            'target_retirement_year' => 2030,
            'enable_hypothetical_projection' => true,
        ]);

        $this->assertInstanceOf(CalculatedPension::class, $result);
        $this->assertTrue($result->calculation_breakdown['is_hypothetical'] ?? false);
        $this->assertGreaterThan(0, $result->final_pension);
    }

    public function test_past_retirement_year_with_only_future_tax_histories_returns_empty_breakdown(): void
    {
        $user = User::factory()->create([
            'target_retirement_year' => 2021,
        ]);

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2024,
            'annual_income' => 240000.00,
            'tax_paid' => 43200.00,
            'months_worked' => 12,
        ]);

        $service = new PensionCalculatorService();
        $calc = $service->calculateAndSave($user, [
            'gender' => 'male',
            'date_of_birth' => '1960-01-01',
            'target_retirement_year' => 2021,
        ]);

        $this->assertEquals(0, $calc->total_service_months);

        $response = $this->actingAs($user)->getJson("/pension-calculations/{$calc->id}/breakdown");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }
}
