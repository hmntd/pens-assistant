<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AgentParserService;
use App\Services\RedisSessionTrackerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexSessionController extends Controller
{
    public function __construct(
        private readonly AgentParserService $agentParser,
        private readonly RedisSessionTrackerService $sessionTracker
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $sessions = $this->getSessionsForUser($request);

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Fetch and format active sessions for the authenticated user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSessionsForUser(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        if ($request->hasSession()) {
            $this->sessionTracker->trackRequestSession($request);
        }

        $currentSessionId = $request->hasSession() ? $request->session()->getId() : '';

        $rows = $this->sessionTracker->getSessionsForUser((string) $user->id);

        return $rows->map(function ($session) use ($currentSessionId) {
            $agentInfo = $this->agentParser->parse($session->user_agent);
            $lastActivityCarbon = Carbon::createFromTimestamp($session->last_activity);

            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address ?? '127.0.0.1',
                'is_current_device' => $session->id === $currentSessionId,
                'device_type' => $agentInfo['device_type'],
                'platform' => $agentInfo['platform'],
                'browser' => $agentInfo['browser'],
                'browser_version' => $agentInfo['browser_version'],
                'user_agent' => $session->user_agent,
                'last_active' => $lastActivityCarbon->diffForHumans(),
                'last_activity_timestamp' => $session->last_activity,
            ];
        })->all();
    }
}
