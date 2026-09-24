<?php

namespace Tests\Feature\Document;

use App\Models\CalculatedPension;
use App\Models\TaxHistory;
use App\Models\User;
use App\Services\PensionCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxHistoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_single_year_tax_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.tax-histories.store'), [
            'is_range' => false,
            'year' => 2024,
            'monthly_salary' => 15000,
            'months_worked' => 12,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tax_histories', [
            'user_id' => $user->id,
            'year' => 2024,
            'annual_income' => 180000.00,
            'months_worked' => 12,
        ]);
    }

    public function test_authenticated_user_can_store_range_tax_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.tax-histories.store'), [
            'is_range' => true,
            'from_year' => 2020,
            'to_year' => 2023,
            'monthly_salary' => 20000,
            'months_worked' => 12,
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('tax_histories', 4);
        $this->assertDatabaseHas('tax_histories', [
            'user_id' => $user->id,
            'year' => 2020,
            'annual_income' => 240000.00,
        ]);
        $this->assertDatabaseHas('tax_histories', [
            'user_id' => $user->id,
            'year' => 2023,
            'annual_income' => 240000.00,
        ]);
    }

    public function test_cannot_store_tax_history_for_future_years(): void
    {
        $user = User::factory()->create();
        $futureYear = (int) date('Y') + 1;

        $response = $this->actingAs($user)->postJson(route('documents.tax-histories.store'), [
            'is_range' => false,
            'year' => $futureYear,
            'monthly_salary' => 15000,
            'months_worked' => 12,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_store_tax_history_range_where_from_year_is_greater_than_or_equal_to_to_year(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('documents.tax-histories.store'), [
            'is_range' => true,
            'from_year' => 2024,
            'to_year' => 2020,
            'monthly_salary' => 15000,
            'months_worked' => 12,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_store_pre_2000_tax_history_without_salary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.tax-histories.store'), [
            'is_range' => false,
            'year' => 1995,
            'monthly_salary' => 0,
            'months_worked' => 12,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tax_histories', [
            'user_id' => $user->id,
            'year' => 1995,
            'annual_income' => 0.00,
            'months_worked' => 12,
        ]);
    }

    public function test_calculation_restricted_when_user_data_is_missing(): void
    {
        $user = User::factory()->create([
            'target_retirement_year' => null,
            'date_of_birth' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('pension-calculations.store'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_retirement_year', 'insurance_service']);
    }

    public function test_calculation_allowed_when_user_data_is_provided(): void
    {
        $user = User::factory()->create([
            'gender' => 'MALE',
            'date_of_birth' => '1970-01-01',
            'target_retirement_year' => 2035,
        ]);

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2024,
            'annual_income' => 200000,
            'tax_paid' => 36000,
            'months_worked' => 12,
        ]);

        $this->mock(PensionCalculatorService::class, function ($mock) use ($user) {
            $mock->shouldReceive('calculateAndSave')
                ->once()
                ->andReturnUsing(fn () => CalculatedPension::create([
                    'user_id' => $user->id,
                    'final_pension' => 8500.00,
                    'base_pension' => 7800.00,
                    'estimated_monthly_pension' => 8500.00,
                    'total_accumulated_capital' => 1872000.00,
                ]));
        });

        $response = $this->actingAs($user)->postJson(route('pension-calculations.store'), [
            'target_retirement_year' => 2035,
        ]);

        $response->assertCreated();
    }
}
