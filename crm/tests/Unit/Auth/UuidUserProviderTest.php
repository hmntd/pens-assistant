<?php

namespace Tests\Unit\Auth;

use App\Auth\UuidEloquentUserProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UuidUserProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_retrieve_by_id_returns_null_when_identifier_is_not_a_valid_uuid(): void
    {
        $provider = new UuidEloquentUserProvider($this->app['hash'], User::class);

        $this->assertNull($provider->retrieveById('1'));
        $this->assertNull($provider->retrieveById(123));
        $this->assertNull($provider->retrieveById('invalid-uuid'));
    }

    public function test_retrieve_by_id_returns_user_when_identifier_is_a_valid_uuid(): void
    {
        $user = User::factory()->create();
        $provider = new UuidEloquentUserProvider($this->app['hash'], User::class);

        $retrieved = $provider->retrieveById($user->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals($user->id, $retrieved->id);
    }

    public function test_retrieve_by_token_returns_null_when_identifier_is_not_a_valid_uuid(): void
    {
        $provider = new UuidEloquentUserProvider($this->app['hash'], User::class);

        $this->assertNull($provider->retrieveByToken('1', 'some-token'));
        $this->assertNull($provider->retrieveByToken('123', 'some-token'));
    }

    public function test_retrieve_by_token_returns_user_when_identifier_is_valid_uuid_and_token_matches(): void
    {
        $token = Str::random(60);
        $user = User::factory()->create([
            'remember_token' => $token,
        ]);
        $provider = new UuidEloquentUserProvider($this->app['hash'], User::class);

        $retrieved = $provider->retrieveByToken($user->id, $token);
        $this->assertNotNull($retrieved);
        $this->assertEquals($user->id, $retrieved->id);
    }
}
