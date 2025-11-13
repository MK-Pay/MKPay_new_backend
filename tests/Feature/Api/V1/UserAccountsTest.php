<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function testListAccountsWithoutAuthentication(): void
    {
        $response = $this->getJson('/api/v1/accounts');

        $response->assertStatus(401);
    }

    public function testListAccountsWithValidToken(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $user->accounts()->attach($account1, ['is_root' => true]);
        $user->accounts()->attach($account2, ['is_root' => false]);

        $response = $this->actingAs($user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [],
        ]);
        $this->assertCount(2, $response->json('data'));
    }

    public function testListAccountsIncludesAccountRelationships(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();

        $user->accounts()->attach($account, ['is_root' => true]);

        $response = $this->actingAs($user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('account_type_id', $data[0]);
        $this->assertArrayHasKey('account_status_id', $data[0]);
        $this->assertArrayHasKey('uuid', $data[0]);
    }

    public function testListOnlyUserOwnedAccounts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $user1->accounts()->attach($account1, ['is_root' => true]);
        $user2->accounts()->attach($account2, ['is_root' => true]);

        $response = $this->actingAs($user1)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($account1->id, $data[0]['id']);
    }

    public function testListAccountsIncludesPivotData(): void
    {
        $user = User::factory()->create();
        $rootAccount = Account::factory()->create();
        $memberAccount = Account::factory()->create();

        $user->accounts()->attach($rootAccount, ['is_root' => true]);
        $user->accounts()->attach($memberAccount, ['is_root' => false]);

        $response = $this->actingAs($user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $data = $response->json('data');

        $rootData = collect($data)->firstWhere('id', $rootAccount->id);
        $memberData = collect($data)->firstWhere('id', $memberAccount->id);

        $this->assertTrue($rootData['pivot']['is_root']);
        $this->assertFalse($memberData['pivot']['is_root']);
    }

    public function testEmptyAccountsList(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [],
        ]);
    }
}
