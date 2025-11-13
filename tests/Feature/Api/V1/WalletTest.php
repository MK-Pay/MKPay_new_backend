<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class WalletTest extends TenantTestCase
{
    use RefreshDatabase;

    protected App $testApp;
    protected Account $account;
    protected Wallet $wallet;
    protected string $appSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->testApp = App::factory()->create(['account_id' => $this->account->id]);

        // Create a secret token for the app
        $this->appSecret = 'test-secret-token-' . bin2hex(random_bytes(16));
        $this->testApp->secretTokens()->create([
            'name' => 'Test Token',
            'token_hash' => bcrypt($this->appSecret),
            'permissions' => ['wallets.read'],
            'is_active' => true,
        ]);

        // Create wallets for the account
        $currency = Currency::first() ?? Currency::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'account_id' => $this->account->id,
            'currency_id' => $currency->id,
        ]);
    }

    protected function getAuthHeaders(): array
    {
        return [
            'X-App-Id' => $this->testApp->app_id,
            'X-App-Secret' => $this->appSecret,
        ];
    }

    public function testListWalletsWithValidCredentials(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['uuid', 'currency', 'balance_available']],
        ]);
    }

    public function testListWalletsWithoutCredentials(): void
    {
        $response = $this->getJson('/api/v1/wallets');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Authentication credentials are required.');
    }

    public function testListWalletsWithInvalidAppSecret(): void
    {
        $response = $this->withHeaders([
            'X-App-Id' => $this->app->app_id,
            'X-App-Secret' => 'invalid-secret',
        ])->getJson('/api/v1/wallets');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid application credentials.');
    }

    public function testListWalletsWithInactiveApp(): void
    {
        $this->app->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Application is inactive.');
    }

    public function testListMultipleWalletsForAccount(): void
    {
        $currency2 = Currency::factory()->create(['code' => 'USD']);
        Wallet::factory()->create([
            'account_uuid' => $this->account->uuid,
            'currency' => $currency2->code,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function testGetWalletDetails(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $response->assertJsonPath('data.uuid', $this->wallet->uuid);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'uuid', 'currency', 'balance_available', 'balance_held',
                'balance_total', 'is_active', 'created_at',
            ],
        ]);
    }

    public function testGetWalletDetailsShowsCorrectBalance(): void
    {
        $this->wallet->update([
            'balance_available' => 1000.00,
            'balance_held' => 500.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $this->assertEquals(1000.00, $response->json('data.balance_available'));
        $this->assertEquals(500.00, $response->json('data.balance_held'));
        $this->assertEquals(1500.00, $response->json('data.balance_total'));
    }

    public function testGetNonExistentWallet(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function testGetWalletBalance(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid . '/balance');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['balance_available', 'balance_held', 'balance_total'],
        ]);
    }

    public function testGetWalletBalanceShowsCorrectValues(): void
    {
        $this->wallet->update([
            'balance_available' => 2500.50,
            'balance_held' => 750.25,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid . '/balance');

        $response->assertStatus(200);
        $this->assertEquals(2500.50, $response->json('data.balance_available'));
        $this->assertEquals(750.25, $response->json('data.balance_held'));
        $this->assertEquals(3250.75, $response->json('data.balance_total'));
    }

    public function testGetWalletStatement(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid . '/statement');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'balance_available', 'balance_held', 'created_at'],
            ],
        ]);
    }

    public function testGetWalletStatementWithPagination(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid . '/statement?per_page=10&page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'balance_available', 'balance_held']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function testGetWalletStatementWithDateFilter(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid . '/statement?from_date=2024-01-01&to_date=2025-12-31');

        $response->assertStatus(200);
    }

    public function testWalletBalanceCalculation(): void
    {
        $this->wallet->update([
            'balance_available' => 1000.00,
            'balance_held' => 200.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $totalBalance = $response->json('data.balance_available') + $response->json('data.balance_held');
        $this->assertEquals($totalBalance, $response->json('data.balance_total'));
    }

    public function testWalletCurrencyDisplay(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.currency'));
    }

    public function testWalletIsActiveStatus(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_active'));
    }

    public function testInactiveWalletIsDisplayed(): void
    {
        $this->wallet->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_active'));
    }

    public function testWalletCreatedAtTimestamp(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.created_at'));
    }

    public function testListWalletsCannotAccessOtherAccountWallets(): void
    {
        $otherAccount = Account::factory()->create();
        $otherWallet = Wallet::factory()->create(['account_id' => $otherAccount->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets');

        $response->assertStatus(200);
        $walletUuids = array_map(fn ($w) => $w['uuid'], $response->json('data'));
        $this->assertNotContains($otherWallet->uuid, $walletUuids);
    }

    public function testGetWalletInvalidUUID(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/invalid-uuid');

        $response->assertStatus(404);
    }

    public function testGetWalletBalanceForNonExistentWallet(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/00000000-0000-0000-0000-000000000000/balance');

        $response->assertStatus(404);
    }

    public function testGetWalletStatementForNonExistentWallet(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/00000000-0000-0000-0000-000000000000/statement');

        $response->assertStatus(404);
    }
}
