<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    public function test_login_returns_user_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->service->login('test@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
    }

    public function test_login_returns_null_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->service->login('test@example.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function test_login_returns_null_with_nonexistent_email(): void
    {
        $result = $this->service->login('nonexistent@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_create_token_returns_plain_text_token(): void
    {
        $user = User::factory()->create();
        $tokenName = 'test-token';

        $token = $this->service->createToken($user, $tokenName);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => $tokenName,
        ]);
    }

    public function test_revoke_all_tokens_deletes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('token1');
        $user->createToken('token2');

        $this->service->revokeAllTokens($user);

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_revoke_current_token_deletes_only_current_token(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('token1');
        $token2 = $user->createToken('token2');

        // Simulate current token
        $user->withAccessToken($token1->accessToken);

        $this->service->revokeCurrentToken($user);

        $this->assertEquals(1, $user->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token1->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token2->accessToken->id,
        ]);
    }
}
