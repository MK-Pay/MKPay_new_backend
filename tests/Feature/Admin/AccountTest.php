<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AccountStatus;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class AccountTest extends TenantTestCase
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

    protected function getAuthHeader(): array
    {
        $loginResponse = $this->postJson('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        return ['Authorization' => "Bearer {$token}"];
    }

    public function testListAccountsWithoutAuth(): void
    {
        $response = $this->getJson('/admin/accounts');

        $response->assertStatus(401);
    }

    public function testListAccountsWithAuth(): void
    {
        // Create some accounts
        Account::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'uuid', 'email', 'name', 'account_type', 'account_status'],
            ],
            'meta' => ['total', 'per_page', 'current_page'],
        ]);
    }

    public function testListAccountsWithPagination(): void
    {
        Account::factory(15)->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts?per_page=10&page=1');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(15, $response->json('meta.total'));
    }

    public function testListAccountsWithFilters(): void
    {
        $accountType = AccountType::first();
        $accountStatus = AccountStatus::first();

        Account::factory(5)->create([
            'account_type_id' => $accountType->id,
            'account_status_id' => $accountStatus->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts?type=' . $accountType->slug . '&status=' . $accountStatus->slug);

        $response->assertStatus(200);
        $this->assertEquals(5, count($response->json('data')));
    }

    public function testListAccountsWithSearch(): void
    {
        $account = Account::factory()->create([
            'email' => 'search@example.com',
            'name' => 'Search Test Account',
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts?search=search@example.com');

        $response->assertStatus(200);
        $this->assertGreater(0, count($response->json('data')));
    }

    public function testCreateAccountPF(): void
    {
        $accountType = AccountType::where('slug', 'pf')->first();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/accounts', [
                'account_type_id' => $accountType->id,
                'email' => 'newaccount@test.com',
                'name' => 'New Account',
                'cpf' => '12345678910', // Valid CPF format
                'phone' => '11999999999',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'uuid', 'email', 'name', 'cpf'],
        ]);
    }

    public function testCreateAccountPJWithValidCNPJ(): void
    {
        $accountType = AccountType::where('slug', 'pj')->first();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/accounts', [
                'account_type_id' => $accountType->id,
                'email' => 'company@test.com',
                'name' => 'Company Account',
                'cnpj' => '12345678000190', // Valid CNPJ format
                'phone' => '1133333333',
            ]);

        $response->assertStatus(201);
    }

    public function testCreateAccountWithInvalidCPF(): void
    {
        $accountType = AccountType::where('slug', 'pf')->first();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/accounts', [
                'account_type_id' => $accountType->id,
                'email' => 'invalid@test.com',
                'name' => 'Invalid CPF Account',
                'cpf' => '00000000000', // Invalid CPF
                'phone' => '11999999999',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cpf');
    }

    public function testGetAccountDetails(): void
    {
        $account = Account::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts/' . $account->uuid);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id', 'uuid', 'email', 'name',
                'account_type', 'account_status', 'account_category',
                'apps_count', 'wallets_count', 'transactions_count',
            ],
        ]);
    }

    public function testGetNonExistentAccount(): void
    {
        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function testUpdateAccount(): void
    {
        $account = Account::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->putJson('/admin/accounts/' . $account->uuid, [
                'name' => 'Updated Name',
                'phone' => '11988888888',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated Name', $response->json('data.name'));
    }

    public function testSuspendAccount(): void
    {
        $account = Account::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/accounts/' . $account->uuid . '/suspend');

        $response->assertStatus(200);
        $account->refresh();
        $this->assertEquals('suspended', $account->accountStatus->slug);
    }

    public function testActivateAccount(): void
    {
        $suspendedStatus = AccountStatus::where('slug', 'suspended')->first();
        $account = Account::factory()->create([
            'account_status_id' => $suspendedStatus->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/accounts/' . $account->uuid . '/activate');

        $response->assertStatus(200);
    }

    public function testVerifyAccount(): void
    {
        $account = Account::factory()->create([
            'verified_at' => null,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/accounts/' . $account->uuid . '/verify');

        $response->assertStatus(200);
        $account->refresh();
        $this->assertNotNull($account->verified_at);
    }

    public function testDeleteAccount(): void
    {
        $account = Account::factory()->create();
        $uuid = $account->uuid;

        $response = $this->withHeaders($this->getAuthHeader())
            ->deleteJson('/admin/accounts/' . $uuid);

        $response->assertStatus(200);

        // Verify soft delete
        $this->assertSoftDeleted('accounts', ['uuid' => $uuid]);
    }

    public function testGetAccountActivity(): void
    {
        $account = Account::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts/' . $account->uuid . '/activity');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'description', 'event', 'created_at'],
            ],
        ]);
    }
}
