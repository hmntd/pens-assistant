<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SocialiteAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_user_can_redirect_to_oauth_provider(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    public function test_user_can_redirect_to_microsoft_oauth_provider(): void
    {
        $response = $this->get('/auth/microsoft/redirect');

        $response->assertStatus(302);
        $this->assertStringContainsString('login.microsoftonline.com', $response->getTargetUrl());
    }

    public function test_unsupported_oauth_provider_returns_404(): void
    {
        $response = $this->get('/auth/unsupported_provider/redirect');

        $response->assertStatus(404);
    }

    public function test_user_can_authenticate_via_oauth_provider_and_create_new_account(): void
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('998877');
        $abstractUser->shouldReceive('getEmail')->andReturn('dev@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Alex Taylor');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

        $response = $this->get('/auth/github/callback');

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'dev@example.com',
            'first_name' => 'Alex',
            'last_name' => 'Taylor',
            'provider_name' => 'github',
            'provider_id' => '998877',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
    }

    public function test_user_can_authenticate_via_oauth_provider_and_link_existing_account(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'provider_name' => null,
            'provider_id' => null,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('554433');
        $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Existing User');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/existing-avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($existingUser);

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing@example.com',
            'provider_name' => 'google',
            'provider_id' => '554433',
        ]);
    }
}
