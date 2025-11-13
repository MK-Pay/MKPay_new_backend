<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);
    }

    public function testLoginWithValidCredentials(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
        $this->assertNotNull($response->json('token'));
    }

    public function testLoginWithInvalidPassword(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function testLoginWithMissingEmail(): void
    {
        $response = $this->postJson('/admin/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function testLoginWithMissingPassword(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function testGetUserInfoWithValidToken(): void
    {
        $loginResponse = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/admin/user');

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'name', 'email']);
        $response->assertJsonPath('email', 'admin@test.com');
    }

    public function testGetUserInfoWithoutToken(): void
    {
        $response = $this->getJson('/admin/user');

        $response->assertStatus(401);
    }

    public function testGetUserInfoWithInvalidToken(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/admin/user');

        $response->assertStatus(401);
    }

    public function testLogoutWithValidToken(): void
    {
        $loginResponse = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/admin/logout');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function testTokenIsInvalidatedAfterLogout(): void
    {
        $loginResponse = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        // Logout
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/admin/logout');

        // Try to use the token after logout
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/admin/user');

        $response->assertStatus(401);
    }

    public function testMultipleLoginsCreateDifferentTokens(): void
    {
        $login1 = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $login2 = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token1 = $login1->json('token');
        $token2 = $login2->json('token');

        // Both tokens should be different
        $this->assertNotEquals($token1, $token2);

        // Both tokens should work
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/admin/user')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/admin/user')
            ->assertStatus(200);
    }

    public function testRateLimitingOnLoginAttempts(): void
    {
        // Attempt login 6 times (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'wrongpassword',
            ]);

            if ($i < 5) {
                $this->assertNotEquals(429, $response->status());
            }
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertEquals(429, $response->status());
    }
}
