<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserIsNotSuspended;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsureUserIsNotSuspendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_guest_requests(): void
    {
        $middleware = new EnsureUserIsNotSuspended();
        $request = Request::create('/dashboard', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_allows_active_user_requests(): void
    {
        $middleware = new EnsureUserIsNotSuspended();
        $user = User::factory()->create(['is_suspended' => false]);
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_blocks_suspended_user_and_logs_out(): void
    {
        $middleware = new EnsureUserIsNotSuspended();
        $user = User::factory()->create(['is_suspended' => true]);
        $this->actingAs($user);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertTrue($response->isRedirection());
        $this->assertGuest();
    }
}
