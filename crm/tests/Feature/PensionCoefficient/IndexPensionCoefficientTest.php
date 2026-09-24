<?php

namespace Tests\Feature\PensionCoefficient;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class IndexPensionCoefficientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_can_list_pension_coefficients_with_pagination(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->getJson(route('pension-coefficients.index', ['page' => 1, 'per_page' => 5]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'year', 'month', 'coefficient', 'description'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJson([
                'status' => 'success',
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 5,
                ],
            ]);
    }
}
