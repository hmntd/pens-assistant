<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubsistenceMinimumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_subsistence_minimums(): void
    {
        $response = $this->getJson('/admin/subsistence-minimums');
        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_access_subsistence_minimums(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/admin/subsistence-minimums');
        $response->assertStatus(403);
    }

    public function test_admin_can_list_subsistence_minimums(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->getJson('/admin/subsistence-minimums');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_store_subsistence_minimum(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $payload = [
            'year' => 2027,
            'for_disabled_persons' => 2900.00,
            'general_minimum' => 3600.00,
        ];

        $response = $this->actingAs($admin)->postJson('/admin/subsistence-minimums', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Subsistence minimum set successfully by admin.',
                'data' => [
                    'year' => 2027,
                    'for_disabled_persons' => 2900.00,
                    'general_minimum' => 3600.00,
                ],
            ]);
    }

    public function test_admin_can_update_subsistence_minimum(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // First store a record for 2028
        $storeResponse = $this->actingAs($admin)->postJson('/admin/subsistence-minimums', [
            'year' => 2028,
            'for_disabled_persons' => 2800.00,
            'general_minimum' => 3500.00,
        ]);
        $storeResponse->assertStatus(201);

        // Fetch list to find created ID
        $listResponse = $this->actingAs($admin)->getJson('/admin/subsistence-minimums');
        $records = $listResponse->json('data');
        $id = $records[0]['id'] ?? 1;

        $payload = [
            'year' => 2028,
            'for_disabled_persons' => 2950.00,
            'general_minimum' => 3650.00,
        ];

        $response = $this->actingAs($admin)->putJson("/admin/subsistence-minimums/{$id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subsistence minimum updated successfully.',
            ]);
    }

    public function test_admin_can_delete_subsistence_minimum(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // First store a record for 2029
        $storeResponse = $this->actingAs($admin)->postJson('/admin/subsistence-minimums', [
            'year' => 2029,
            'for_disabled_persons' => 2800.00,
            'general_minimum' => 3500.00,
        ]);
        $storeResponse->assertStatus(201);

        // Fetch list to find created ID
        $listResponse = $this->actingAs($admin)->getJson('/admin/subsistence-minimums');
        $records = $listResponse->json('data');
        $id = $records[0]['id'];

        $response = $this->actingAs($admin)->deleteJson("/admin/subsistence-minimums/{$id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subsistence minimum deleted successfully.',
            ]);
    }
}
