<?php

namespace Tests\Feature\Settings;

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class BrowserSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_active_browser_sessions(): void
    {
        $user = User::factory()->create();

        Session::create([
            'id' => 'session-1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($user)->get(route('sessions.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true);
    }

    public function test_security_page_includes_active_sessions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->has('sessions'));
    }

    public function test_user_can_destroy_a_specific_other_browser_session(): void
    {
        $user = User::factory()->create();

        Session::create([
            'id' => 'session-to-delete',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($user)->delete(route('sessions.destroy', 'session-to-delete'));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing(Session::class, ['id' => 'session-to-delete']);
    }

    public function test_user_cannot_destroy_current_active_session_via_single_revoke(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('security.edit'))
            ->delete(route('sessions.destroy', 'current'));

        $response->assertSessionHasErrors('session');
    }

    public function test_user_can_log_out_other_browser_sessions_with_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        Session::insert([
            [
                'id' => 'session-other-1',
                'user_id' => $user->id,
                'ip_address' => '192.168.1.20',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13)',
                'payload' => 'payload',
                'last_activity' => time() - 300,
            ],
            [
                'id' => 'session-other-2',
                'user_id' => $user->id,
                'ip_address' => '192.168.1.30',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'payload' => 'payload',
                'last_activity' => time() - 600,
            ],
        ]);

        $response = $this->actingAs($user)->post(route('sessions.logout-other'), [
            'password' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing(Session::class, ['id' => 'session-other-1']);
        $this->assertDatabaseMissing(Session::class, ['id' => 'session-other-2']);
    }

    public function test_logout_other_sessions_fails_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->actingAs($user)->post(route('sessions.logout-other'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
