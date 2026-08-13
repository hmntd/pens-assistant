<?php

namespace Tests\Feature\PensionCoefficient;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class DeletePensionCoefficientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_can_delete_pension_coefficient(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $createResponse = $this->actingAs($admin)->postJson(route('pension-coefficients.store'), [
            'year' => 2030,
            'month' => 7,
            'coefficient' => 1.120,
            'description' => 'To be deleted',
        ]);

        $id = $createResponse->json('data.id') ?? 1;

        $deleteResponse = $this->actingAs($admin)->deleteJson(route('pension-coefficients.destroy', ['id' => $id]));

        $deleteResponse->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pension coefficient deleted successfully',
            ]);
    }
}
