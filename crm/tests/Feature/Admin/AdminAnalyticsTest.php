<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\CalculatedPension;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_access_admin_analytics(): void
    {
        $response = $this->getJson('/admin/analytics');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_access_admin_analytics(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/admin/analytics');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_analytics(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        CalculatedPension::create([
            'user_id' => $regularUser->id,
            'final_pension' => 8500.00,
            'base_pension' => 7800.00,
            'zp_macroeconomic_average' => 13559.41,
            'kz_wage_coefficient' => 1.85,
            'ks_service_coefficient' => 0.4000,
            'total_service_months' => 480,
            'pension_type' => 'old_age',
            'disability_group' => 'none',
            'estimated_pension' => 8500.00,
            'estimated_monthly_pension' => 8500.00,
            'total_accumulated_capital' => 1872000.00,
        ]);

        Document::create([
            'user_id' => $regularUser->id,
            'file_path' => 'documents/test.pdf',
            'original_filename' => 'test.pdf',
            'document_type' => 'income_certificate',
            'status' => 'completed',
        ]);

        AuditLog::create([
            'user_id' => $regularUser->id,
            'action' => 'Document Uploaded',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/analytics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'summary' => [
                    'total_users',
                    'active_users_30d',
                    'total_calculations',
                    'total_documents',
                    'total_tax_histories',
                    'avg_pension_amount',
                    'avg_wage_coefficient',
                ],
                'entry_methods' => [
                    'ocr_count',
                    'manual_count',
                    'ocr_percentage',
                    'manual_percentage',
                ],
                'document_statuses',
                'browsers',
                'operating_systems',
                'device_types',
                'timeline',
                'recent_logs',
            ],
        ]);

        $this->assertEquals(User::count(), $response->json('data.summary.total_users'));
        $this->assertEquals(1, $response->json('data.summary.total_calculations'));
        $this->assertEquals(8500.00, $response->json('data.summary.avg_pension_amount'));
    }
}
