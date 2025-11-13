<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class WalletTest extends TenantTestCase
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

    public function testListWallets(): void
    {
        Wallet::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/wallets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uuid', 'account_uuid', 'currency',
                    'balance_available', 'balance_held', 'balance_total',
                ],
            ],
            'meta' => ['total'],
        ]);
    }

    public function testGetWalletsByAccount(): void
    {
        $account = Account::factory()->create();
        Wallet::factory(2)->create(['account_uuid' => $account->uuid]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts/' . $account->uuid . '/wallets');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    public function testCreateWallet(): void
    {
        $account = Account::factory()->create();
        $currency = Currency::first();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/wallets', [
                'account_uuid' => $account->uuid,
                'currency_id' => $currency->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['uuid', 'account_uuid', 'currency_id', 'balance_available'],
        ]);
    }

    public function testGetWalletDetails(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/wallets/' . $wallet->uuid);

        $response->assertStatus(200);
        $response->assertJsonPath('data.uuid', $wallet->uuid);
        $response->assertJsonStructure([
            'data' => [
                'uuid', 'currency', 'balance_available', 'balance_held',
                'balance_total', 'is_active', 'created_at',
            ],
        ]);
    }

    public function testAdjustWalletBalance(): void
    {
        $wallet = Wallet::factory()->create([
            'balance_available' => 1000,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/wallets/' . $wallet->uuid . '/adjust', [
                'amount' => 500,
                'type' => 'credit',
                'reason' => 'Manual adjustment for testing',
            ]);

        $response->assertStatus(200);
        $wallet->refresh();
        $this->assertEquals(1500, $wallet->balance_available);
    }

    public function testDebitWallet(): void
    {
        $wallet = Wallet::factory()->create([
            'balance_available' => 1000,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/wallets/' . $wallet->uuid . '/adjust', [
                'amount' => 200,
                'type' => 'debit',
                'reason' => 'Test debit',
            ]);

        $response->assertStatus(200);
        $wallet->refresh();
        $this->assertEquals(800, $wallet->balance_available);
    }

    public function testCannotDebitMoreThanAvailable(): void
    {
        $wallet = Wallet::factory()->create([
            'balance_available' => 500,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/wallets/' . $wallet->uuid . '/adjust', [
                'amount' => 1000,
                'type' => 'debit',
                'reason' => 'Test debit',
            ]);

        $response->assertStatus(422);
    }

    public function testGetWalletBalanceHistory(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/wallets/' . $wallet->uuid . '/balances');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'balance_available', 'balance_held', 'created_at'],
            ],
        ]);
    }

    public function testGetWalletTransactions(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/wallets/' . $wallet->uuid . '/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['uuid', 'amount', 'type', 'status'],
            ],
        ]);
    }

    public function testDeleteWallet(): void
    {
        $wallet = Wallet::factory()->create();
        $uuid = $wallet->uuid;

        $response = $this->withHeaders($this->getAuthHeader())
            ->deleteJson('/admin/wallets/' . $uuid);

        $response->assertStatus(200);

        // Wallet should be soft deleted
        $this->assertSoftDeleted('wallets', ['uuid' => $uuid]);
    }

    public function testCreateWalletRequiresValidAccount(): void
    {
        $currency = Currency::first();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/wallets', [
                'account_uuid' => '00000000-0000-0000-0000-000000000000',
                'currency_id' => $currency->id,
            ]);

        $response->assertStatus(422);
    }

    public function testCreateWalletRequiresValidCurrency(): void
    {
        $account = Account::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/wallets', [
                'account_uuid' => $account->uuid,
                'currency_id' => 9999, // Non-existent currency
            ]);

        $response->assertStatus(422);
    }
}
