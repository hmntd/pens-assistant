<?php

namespace Tests\Feature\PensionCoefficient;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class UpdatePensionCoefficientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_can_update_pension_coefficient(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $createResponse = $this->actingAs($admin)->postJson(route('pension-coefficients.store'), [
            'year' => 2029,
            'month' => 6,
            'coefficient' => 1.100,
            'description' => 'Initial',
        ]);

        $id = $createResponse->json('data.id') ?? 1;

        $updateResponse = $this->actingAs($admin)->putJson(route('pension-coefficients.update', ['id' => $id]), [
            'year' => 2029,
            'month' => 6,
            'coefficient' => 1.115,
            'description' => 'Updated',
        ]);

        $updateResponse->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $id,
                    'coefficient' => 1.115,
                    'description' => 'Updated',
                ],
            ]);
    }
}
