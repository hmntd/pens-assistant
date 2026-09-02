<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        Role::findOrCreate('user');
    }

    public function test_active_user_can_access_protected_routes_and_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_suspended' => false,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $this->post('/logout');

        $loginResponse = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_user_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'is_suspended' => true,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_receives_403_json_on_api_request(): void
    {
        $user = User::factory()->create([
            'is_suspended' => true,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson(route('dashboard'));

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');

        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login_with_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_suspended' => true,
        ]);
        $user->assignRole('user');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login_via_socialite_oauth(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'provider_name' => 'google',
            'provider_id' => '123456789',
            'is_suspended' => true,
        ]);
        $user->assignRole('user');

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getEmail')->andReturn('suspended@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Suspended User');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Contract\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_toggling_user_suspension_immediately_blocks_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['is_suspended' => false]);
        $user->assignRole('user');

        // Admin toggles suspension to true
        $toggleResponse = $this->actingAs($admin)->postJson(route('admin.users.toggle-suspend', ['id' => $user->id]));
        $toggleResponse->assertOk()->assertJsonPath('status', 'success');
        $this->assertTrue((bool) $user->fresh()->is_suspended);

        // User now tries to access dashboard
        $userResponse = $this->actingAs($user->fresh())->get(route('dashboard'));
        $userResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
