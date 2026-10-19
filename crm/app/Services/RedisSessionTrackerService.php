<?php

namespace App\Services;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RedisSessionTrackerService
{
    private const SESSION_INDEX_PREFIX = 'user_sessions_index:';

    private const SESSION_META_PREFIX = 'session_meta:';

    private const GLOBAL_USER_AGENTS_KEY = 'analytics:session_user_agents';

    /**
     * Track the current authenticated user's session in Cache/Redis.
     */
    public function trackRequestSession(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $request->hasSession()) {
            return;
        }

        $sessionId = $request->session()->getId();
        if (empty($sessionId)) {
            return;
        }

        $ttlSeconds = max(60, ((int) config('session.lifetime', 120)) * 60);
        $now = time();

        $meta = [
            'id' => $sessionId,
            'user_id' => (string) $user->id,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => (string) ($request->userAgent() ?? ''),
            'last_activity' => $now,
        ];

        try {
            Cache::put(self::SESSION_META_PREFIX.$sessionId, $meta, $ttlSeconds);

            $indexKey = self::SESSION_INDEX_PREFIX.$user->id;
            /** @var array<int, string> $sessionIds */
            $sessionIds = Cache::get($indexKey, []);
            if (! in_array($sessionId, $sessionIds, true)) {
                $sessionIds[] = $sessionId;
            }
            Cache::put($indexKey, array_values($sessionIds), $ttlSeconds);

            // Also track user agents for Admin Analytics when sessions are in Redis
            if (! empty($meta['user_agent'])) {
                /** @var array<string, string> $uaMap */
                $uaMap = Cache::get(self::GLOBAL_USER_AGENTS_KEY, []);
                $uaMap[$sessionId] = $meta['user_agent'];
                if (count($uaMap) > 500) {
                    $uaMap = array_slice($uaMap, -500, null, true);
                }
                Cache::put(self::GLOBAL_USER_AGENTS_KEY, $uaMap, $ttlSeconds);
            }
        } catch (Throwable) {
            // Silently ignore cache write issues
        }
    }

    /**
     * Get all active sessions for a user (merges Redis/Cache tracked sessions and DB Session rows).
     *
     * @return Collection<int, \stdClass>
     */
    public function getSessionsForUser(string $userId): Collection
    {
        /** @var array<string, \stdClass> $sessionsById */
        $sessionsById = [];

        // 1. Check Cache/Redis tracked sessions
        try {
            $indexKey = self::SESSION_INDEX_PREFIX.$userId;
            /** @var array<int, string> $sessionIds */
            $sessionIds = Cache::get($indexKey, []);
            $activeIds = [];

            foreach ($sessionIds as $sid) {
                /** @var array<string, mixed>|null $meta */
                $meta = Cache::get(self::SESSION_META_PREFIX.$sid);
                if (is_array($meta) && isset($meta['id'])) {
                    $activeIds[] = $sid;
                    $sessionsById[$sid] = (object) [
                        'id' => (string) $meta['id'],
                        'user_id' => (string) ($meta['user_id'] ?? $userId),
                        'ip_address' => isset($meta['ip_address']) ? (string) $meta['ip_address'] : '127.0.0.1',
                        'user_agent' => isset($meta['user_agent']) ? (string) $meta['user_agent'] : '',
                        'last_activity' => (int) ($meta['last_activity'] ?? time()),
                    ];
                }
            }

            if (count($activeIds) !== count($sessionIds)) {
                $ttlSeconds = max(60, ((int) config('session.lifetime', 120)) * 60);
                Cache::put($indexKey, $activeIds, $ttlSeconds);
            }
        } catch (Throwable) {
            // Fallback to DB
        }

        // 2. Merge database sessions table records (supports database driver & tests)
        try {
            $dbRows = Session::query()
                ->where('user_id', $userId)
                ->orderBy('last_activity', 'desc')
                ->get();

            foreach ($dbRows as $row) {
                $sessionsById[$row->id] = (object) [
                    'id' => (string) $row->id,
                    'user_id' => (string) ($row->user_id ?? $userId),
                    'ip_address' => $row->ip_address !== null ? (string) $row->ip_address : null,
                    'user_agent' => $row->user_agent !== null ? (string) $row->user_agent : null,
                    'last_activity' => (int) $row->last_activity,
                ];
            }
        } catch (Throwable) {
            // Ignore if DB table not accessible
        }

        /** @var Collection<int, \stdClass> $result */
        $result = collect(array_values($sessionsById))
            ->sortByDesc('last_activity')
            ->values();

        return $result;
    }

    /**
     * Revoke a specific session for a user in both Redis/Cache and Database.
     */
    public function destroySession(string $userId, string $sessionId): void
    {
        try {
            Cache::forget(self::SESSION_META_PREFIX.$sessionId);

            // Also remove underlying Laravel cache/redis session key if stored via CacheHandler
            $sessionStore = config('session.store') ?: config('cache.default');
            Cache::store($sessionStore)->forget($sessionId);
            Cache::forget($sessionId);

            $indexKey = self::SESSION_INDEX_PREFIX.$userId;
            /** @var array<int, string> $sessionIds */
            $sessionIds = Cache::get($indexKey, []);
            $filtered = array_values(array_filter($sessionIds, fn (string $id) => $id !== $sessionId));
            $ttlSeconds = max(60, ((int) config('session.lifetime', 120)) * 60);
            Cache::put($indexKey, $filtered, $ttlSeconds);

            /** @var array<string, string> $uaMap */
            $uaMap = Cache::get(self::GLOBAL_USER_AGENTS_KEY, []);
            if (isset($uaMap[$sessionId])) {
                unset($uaMap[$sessionId]);
                Cache::put(self::GLOBAL_USER_AGENTS_KEY, $uaMap, $ttlSeconds);
            }
        } catch (Throwable) {
            // Ignore cache errors
        }

        try {
            Session::query()
                ->where('id', $sessionId)
                ->where('user_id', $userId)
                ->delete();
        } catch (Throwable) {
            // Ignore DB errors
        }
    }

    /**
     * Revoke all sessions for a user except the current session ID.
     */
    public function destroyOtherSessions(string $userId, string $currentSessionId): void
    {
        try {
            $indexKey = self::SESSION_INDEX_PREFIX.$userId;
            /** @var array<int, string> $sessionIds */
            $sessionIds = Cache::get($indexKey, []);
            $sessionStore = config('session.store') ?: config('cache.default');

            foreach ($sessionIds as $sid) {
                if ($sid !== $currentSessionId) {
                    Cache::forget(self::SESSION_META_PREFIX.$sid);
                    Cache::store($sessionStore)->forget($sid);
                    Cache::forget($sid);
                }
            }

            $remaining = in_array($currentSessionId, $sessionIds, true) ? [$currentSessionId] : [];
            $ttlSeconds = max(60, ((int) config('session.lifetime', 120)) * 60);
            Cache::put($indexKey, $remaining, $ttlSeconds);
        } catch (Throwable) {
            // Ignore cache errors
        }

        try {
            Session::query()
                ->where('user_id', $userId)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        } catch (Throwable) {
            // Ignore DB errors
        }
    }

    /**
     * Get tracked session user agents for analytics.
     *
     * @return array<int, string>
     */
    public function getTrackedUserAgents(): array
    {
        try {
            /** @var array<string, string> $uaMap */
            $uaMap = Cache::get(self::GLOBAL_USER_AGENTS_KEY, []);

            return array_values(array_filter($uaMap));
        } catch (Throwable) {
            return [];
        }
    }
}
