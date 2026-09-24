<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_notification_settings_page(): void
    {
        $response = $this->get('/settings/notifications');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_notification_settings_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/notifications');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Notifications')
            ->has('channels')
            ->where('userEmail', $user->email)
        );
    }

    public function test_user_can_update_notification_channels_and_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/settings/notifications', [
            'email_enabled' => true,
            'telegram_enabled' => true,
            'telegram_chat_id' => '987654321',
            'notify_calc_completed' => true,
            'notify_document_processed' => true,
            'notify_system_alerts' => false,
            'notify_pension_updates' => true,
        ]);

        $response->assertRedirect('/settings/notifications');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('user_notification_channels', [
            'user_id' => $user->id,
            'telegram_enabled' => true,
            'telegram_chat_id' => '987654321',
            'notify_system_alerts' => false,
            'notify_pension_updates' => true,
        ]);
    }

    public function test_user_can_send_test_notification_to_channel(): void
    {
        Http::fake();

        $user = User::factory()->create();
        UserNotificationChannel::create([
            'user_id' => $user->id,
            'telegram_enabled' => true,
            'telegram_chat_id' => '123456789',
        ]);

        $response = $this->actingAs($user)->postJson('/settings/notifications/test', [
            'channel' => 'telegram',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
