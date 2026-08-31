<?php

namespace Tests\Feature\Admin;

use App\Models\SystemErrorLog;
use App\Models\User;
use App\Services\SystemErrorLoggerService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSystemErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        Role::findOrCreate('user');
    }

    public function test_guest_cannot_access_system_errors(): void
    {
        $response = $this->getJson('/admin/system-errors');

        $response->assertUnauthorized();
    }

    public function test_regular_user_cannot_access_system_errors(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/admin/system-errors');

        $response->assertForbidden();
    }

    public function test_admin_can_list_system_errors_and_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SystemErrorLog::create([
            'status_code' => 502,
            'url' => 'https://example.com/test-endpoint',
            'method' => 'POST',
            'exception_class' => Exception::class,
            'message' => 'gRPC service unavailable',
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/system-errors');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.unresolved', 1);
    }

    public function test_admin_can_toggle_resolved_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $errorLog = SystemErrorLog::create([
            'status_code' => 500,
            'url' => 'https://example.com/api/test',
            'method' => 'GET',
            'exception_class' => Exception::class,
            'message' => 'Internal server error',
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($admin)->patchJson("/admin/system-errors/{$errorLog->id}/toggle-resolve");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_resolved', true);

        $this->assertDatabaseHas('system_error_logs', [
            'id' => $errorLog->id,
            'is_resolved' => true,
            'resolved_by_id' => $admin->id,
        ]);
    }

    public function test_admin_can_batch_resolve_system_errors(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $log1 = SystemErrorLog::create([
            'status_code' => 500,
            'url' => 'https://example.com/api/1',
            'method' => 'GET',
            'exception_class' => Exception::class,
            'message' => 'Error 1',
            'is_resolved' => false,
        ]);

        $log2 = SystemErrorLog::create([
            'status_code' => 502,
            'url' => 'https://example.com/api/2',
            'method' => 'GET',
            'exception_class' => Exception::class,
            'message' => 'Error 2',
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/system-errors/batch-resolve', [
            'ids' => [$log1->id, $log2->id],
            'is_resolved' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('system_error_logs', ['id' => $log1->id, 'is_resolved' => true]);
        $this->assertDatabaseHas('system_error_logs', ['id' => $log2->id, 'is_resolved' => true]);
    }

    public function test_logger_service_creates_db_record_and_notifies_admins(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $service = new SystemErrorLoggerService();

        $request = Request::create('/test-url', 'GET');
        $exception = new Exception('Test runtime failure');

        $log = $service->logException($exception, $request, 502);

        $this->assertNotNull($log);
        $this->assertEquals(502, $log->status_code);
        $this->assertEquals('Test runtime failure', $log->message);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'error',
        ]);
    }
}
