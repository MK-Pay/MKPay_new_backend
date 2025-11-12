<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function testNewUsersCanRegister(): void
    {
        $user = User::factory()->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->post(route('api.v1.auth.register'), [
            'name' => $user->name,
            'email' => $user->email,

            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $authResponse = $this->post(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $authResponse->assertStatus(200);

        $authResponse->assertJson(
            fn ($json) =>
            $json->whereType('token', 'string')
        );

        $token = $authResponse->json('token');

        // Assert — test if token authenticates correctly
        $userDataResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.v1.auth.me'));

        $userDataResponse->assertOk();

        $userDataResponse->assertOk()
            ->assertJsonPath('email', $user->email);
    }
}
