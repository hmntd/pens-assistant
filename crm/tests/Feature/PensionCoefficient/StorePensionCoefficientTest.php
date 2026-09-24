<?php

namespace Tests\Feature\PensionCoefficient;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class StorePensionCoefficientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_can_store_pension_coefficient(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $payload = [
            'year' => 2028,
            'month' => 5,
            'coefficient' => 1.095,
            'description' => 'Травень 2028 - Прогноз',
        ];

        $response = $this->actingAs($admin)->postJson(route('pension-coefficients.store'), $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'year' => 2028,
                    'month' => 5,
                    'coefficient' => 1.095,
                    'description' => 'Травень 2028 - Прогноз',
                ],
            ]);
    }

    public function test_store_validation_fails_for_invalid_month(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $payload = [
            'year' => 2028,
            'month' => 15,
            'coefficient' => 1.095,
        ];

        $response = $this->actingAs($admin)->postJson(route('pension-coefficients.store'), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['month']);
    }
}
