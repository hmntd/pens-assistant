<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonitoringAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_pass_admin_auth_check(): void
    {
        $response = $this->get('/admin/auth-check');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_regular_user_cannot_pass_admin_auth_check(): void
    {
        $role = Role::firstOrCreate(['name' => 'user']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin/auth-check');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_user_passes_admin_auth_check(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $response = $this->actingAs($admin)->get('/admin/auth-check');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertSee('OK');
    }
}
