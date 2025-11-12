<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function testUsersCanAuthenticateUsingTheLoginScreen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
    }

    public function testUsersCanNotAuthenticateWithInvalidPassword(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertTrue($response->status() !== 200);
    }

    public function testUsersCanLogout(): void
    {
        $user = User::factory()->create();
        $okStatuses = range(200, 202);
        $sanctumToken = $user->createToken('testToken') ?? null;
        $token = $sanctumToken?->plainTextToken ?? null;

        $this->assertNotEmpty($token);

        $userDataResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.v1.auth.me'));

        $userDataResponse->assertOk();

        $logoffResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.v1.auth.logout'));

        $logoffResponse->assertNoContent();

        $testTokenResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.v1.auth.logout'));

        $testTokenResponse->assertNoContent();

        $this->assertFalse(in_array($testTokenResponse->status(), $okStatuses));
    }
}
