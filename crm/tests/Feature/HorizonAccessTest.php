<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_horizon_dashboard(): void
    {
        $response = $this->get('/horizon');

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_horizon_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/horizon');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_horizon_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/horizon');

        $response->assertStatus(200);
    }
}
