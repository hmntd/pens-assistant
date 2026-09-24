<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_list_users_with_search_and_pagination(): void
    {
        $admin = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);
        $admin->assignRole('admin');

        User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);

        $response = $this->actingAs($admin)->getJson(route('admin.users.index', ['search' => 'john']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertEquals(1, count($response->json('data.data')));
    }

    public function test_admin_can_view_extended_user_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->getJson(route('admin.users.show', ['id' => $user->id]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($admin)->putJson(route('admin.users.update-role', ['id' => $user->id]), [
            'role' => 'admin',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_admin_can_toggle_suspend_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['is_suspended' => false]);

        $response = $this->actingAs($admin)->postJson(route('admin.users.toggle-suspend', ['id' => $user->id]));

        $response->assertStatus(Response::HTTP_OK);
        $this->assertTrue((bool) $user->fresh()->is_suspended);
    }

    public function test_admin_can_soft_delete_and_restore_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        // Soft delete
        $responseDelete = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', ['id' => $user->id]));
        $responseDelete->assertStatus(Response::HTTP_OK);
        $this->assertTrue($user->fresh()->trashed());

        // Restore
        $responseRestore = $this->actingAs($admin)->postJson(route('admin.users.restore', ['id' => $user->id]));
        $responseRestore->assertStatus(Response::HTTP_OK);
        $this->assertFalse($user->fresh()->trashed());
    }

    public function test_admin_cannot_update_own_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->putJson(route('admin.users.update-role', ['id' => $admin->id]), [
            'role' => 'user',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', ['id' => $admin->id]));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertFalse($admin->fresh()->trashed());
    }

    public function test_regular_user_cannot_access_user_management_endpoints(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $response = $this->actingAs($regularUser)->getJson(route('admin.users.index'));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
