<?php

namespace Tests\Feature;

use App\Models\CalculatedPension;
use App\Models\Document;
use App\Models\Notification;
use App\Models\PfuNews;
use App\Models\SystemErrorLog;
use App\Models\TaxHistory;
use App\Models\User;
use App\Services\RedisSessionTrackerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RedisCacheArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pfu_news_cache_is_invalidated_when_news_is_saved_or_deleted(): void
    {
        Cache::put('pfu_news:latest_3', ['stale'], 3600);
        $this->assertTrue(Cache::has('pfu_news:latest_3'));

        $news = PfuNews::create([
            'title' => 'PFU Cache Test',
            'url' => 'https://www.pfu.gov.ua/test-cache',
            'published_date' => '01.01.2026',
            'published_at' => now(),
        ]);

        $this->assertFalse(Cache::has('pfu_news:latest_3'));

        Cache::put('pfu_news:latest_3', ['populated'], 3600);
        $news->delete();

        $this->assertFalse(Cache::has('pfu_news:latest_3'));
    }

    public function test_user_dashboard_and_entity_caches_are_invalidated_on_model_writes(): void
    {
        $user = User::factory()->create();

        Cache::put("user:{$user->id}:calculations", ['stale'], 600);
        Cache::put("user:{$user->id}:documents", ['stale'], 600);
        Cache::put("user:{$user->id}:tax_histories", ['stale'], 600);
        Cache::put("user:{$user->id}:notifications", ['stale'], 300);
        Cache::put('admin:analytics:summary', ['stale'], 300);

        CalculatedPension::create([
            'user_id' => $user->id,
            'status' => 'completed',
            'estimated_monthly_pension' => 6000,
            'total_accumulated_capital' => 150000,
        ]);

        $this->assertFalse(Cache::has("user:{$user->id}:calculations"));
        $this->assertFalse(Cache::has('admin:analytics:summary'));

        Document::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'original_filename' => 'passport.pdf',
            'file_path' => 'documents/passport.pdf',
            'status' => 'pending',
        ]);

        $this->assertFalse(Cache::has("user:{$user->id}:documents"));

        TaxHistory::create([
            'user_id' => $user->id,
            'year' => 2025,
            'annual_income' => 240000,
            'tax_paid' => 43200,
            'months_worked' => 12,
        ]);

        $this->assertFalse(Cache::has("user:{$user->id}:tax_histories"));

        Notification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Test Notification',
            'message' => 'Cache invalidation test',
            'is_read' => false,
        ]);

        $this->assertFalse(Cache::has("user:{$user->id}:notifications"));
    }

    public function test_system_error_stats_cache_is_invalidated_on_error_log_write(): void
    {
        Cache::put('admin:system_errors:stats', ['stale' => 1], 120);
        $this->assertTrue(Cache::has('admin:system_errors:stats'));

        SystemErrorLog::create([
            'status_code' => 500,
            'url' => '/dashboard',
            'method' => 'GET',
            'exception_class' => 'RuntimeException',
            'message' => 'Simulated error for cache test',
            'is_resolved' => false,
        ]);

        $this->assertFalse(Cache::has('admin:system_errors:stats'));
    }

    public function test_redis_session_tracker_service_tracks_and_revokes_sessions(): void
    {
        $user = User::factory()->create();
        $tracker = app(RedisSessionTrackerService::class);

        Cache::put('session_meta:sess-1', [
            'id' => 'sess-1',
            'user_id' => (string) $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 Chrome/128.0',
            'last_activity' => 1700000000,
        ], 3600);
        Cache::put('session_meta:sess-2', [
            'id' => 'sess-2',
            'user_id' => (string) $user->id,
            'ip_address' => '192.168.1.50',
            'user_agent' => 'Mozilla/5.0 Safari/605.1',
            'last_activity' => 1700000100,
        ], 3600);
        Cache::put("user_sessions_index:{$user->id}", ['sess-1', 'sess-2'], 3600);

        $sessions = $tracker->getSessionsForUser($user->id);
        $this->assertCount(2, $sessions);
        $first = $sessions->first();
        $this->assertNotNull($first);
        $this->assertSame('sess-2', $first->id);

        $tracker->destroySession($user->id, 'sess-1');

        $remaining = $tracker->getSessionsForUser($user->id);
        $this->assertCount(1, $remaining);
        $remFirst = $remaining->first();
        $this->assertNotNull($remFirst);
        $this->assertSame('sess-2', $remFirst->id);

        Cache::put('session_meta:sess-3', [
            'id' => 'sess-3',
            'user_id' => (string) $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => 'Mozilla/5.0 Firefox/130.0',
            'last_activity' => 1700000200,
        ], 3600);
        Cache::put("user_sessions_index:{$user->id}", ['sess-2', 'sess-3'], 3600);

        $tracker->destroyOtherSessions($user->id, 'sess-3');

        $final = $tracker->getSessionsForUser($user->id);
        $this->assertCount(1, $final);
        $finalFirst = $final->first();
        $this->assertNotNull($finalFirst);
        $this->assertSame('sess-3', $finalFirst->id);
    }
}
