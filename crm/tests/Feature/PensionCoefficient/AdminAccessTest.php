<?php

namespace Tests\Feature\PensionCoefficient;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_pension_coefficients(): void
    {
        $response = $this->getJson(route('pension-coefficients.index'));
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_regular_user_cannot_access_pension_coefficients(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson(route('pension-coefficients.index'));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_access_pension_coefficients(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->getJson(route('pension-coefficients.index'));
        $response->assertStatus(Response::HTTP_OK);
    }
}
