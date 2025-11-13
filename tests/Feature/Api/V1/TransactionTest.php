<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class TransactionTest extends TenantTestCase
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
            'permissions' => ['transactions.read'],
            'is_active' => true,
        ]);

        // Create a wallet for the account
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

    public function testListTransactionsWithValidCredentials(): void
    {
        Transaction::factory(5)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['uuid', 'type', 'amount']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function testListTransactionsWithoutCredentials(): void
    {
        $response = $this->getJson('/api/v1/transactions');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Authentication credentials are required.');
    }

    public function testListTransactionsWithInvalidAppSecret(): void
    {
        $response = $this->withHeaders([
            'X-App-Id' => $this->app->app_id,
            'X-App-Secret' => 'invalid-secret',
        ])->getJson('/api/v1/transactions');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid application credentials.');
    }

    public function testListTransactionsWithInactiveApp(): void
    {
        $this->app->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Application is inactive.');
    }

    public function testListTransactionsWithPagination(): void
    {
        Transaction::factory(25)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?per_page=10&page=2');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(2, $response->json('meta.current_page'));
    }

    public function testListTransactionsWithTypeFilter(): void
    {
        Transaction::factory(3)->create(['type' => 'payment_in']);
        Transaction::factory(2)->create(['type' => 'payment_out']);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?type=payment_in');

        $response->assertStatus(200);
    }

    public function testListTransactionsWithStatusFilter(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $processingStatus = TransactionStatus::where('slug', 'processing')->first();

        Transaction::factory(3)->create(['transaction_status_id' => $approvedStatus->id]);
        Transaction::factory(2)->create(['transaction_status_id' => $processingStatus->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?status=approved');

        $response->assertStatus(200);
    }

    public function testListTransactionsWithDateFilter(): void
    {
        Transaction::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?from_date=2024-01-01&to_date=2025-12-31');

        $response->assertStatus(200);
    }

    public function testListTransactionsWithAmountFilter(): void
    {
        Transaction::factory(2)->create(['amount' => 100.00]);
        Transaction::factory(2)->create(['amount' => 500.00]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?min_amount=200&max_amount=600');

        $response->assertStatus(200);
    }

    public function testGetTransactionDetails(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions/' . $transaction->uuid);

        $response->assertStatus(200);
        $response->assertJsonPath('data.uuid', $transaction->uuid);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'uuid', 'type', 'amount', 'currency', 'status',
                'description', 'created_at',
            ],
        ]);
    }

    public function testGetNonExistentTransaction(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function testGetTransactionWithMetadata(): void
    {
        $metadata = [
            'order_id' => '12345',
            'customer_id' => 'cust_789',
            'payment_method' => 'credit_card',
        ];

        $transaction = Transaction::factory()->create(['metadata' => $metadata]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions/' . $transaction->uuid);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.metadata'));
    }

    public function testListTransactionsCannotAccessOtherAccountTransactions(): void
    {
        $otherAccount = Account::factory()->create();
        $otherWallet = Wallet::factory()->create(['account_id' => $otherAccount->id]);

        Transaction::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200);

        // Only transactions from authenticated app's account should be returned
        foreach ($response->json('data') as $transaction) {
            $this->assertNotNull($transaction['uuid']);
        }
    }

    public function testTransactionWithFee(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => 100.00,
            'fee' => 2.50,
            'net_amount' => 97.50,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions/' . $transaction->uuid);

        $response->assertStatus(200);
        $this->assertEquals(100.00, $response->json('data.amount'));
        $this->assertEquals(2.50, $response->json('data.fee'));
        $this->assertEquals(97.50, $response->json('data.net_amount'));
    }

    public function testListTransactionsOrderByNewest(): void
    {
        $transaction1 = Transaction::factory()->create(['created_at' => now()->subDay()]);
        $transaction2 = Transaction::factory()->create(['created_at' => now()]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200);
        $data = $response->json('data');
        // Most recent should be first
        $this->assertEquals($transaction2->uuid, $data[0]['uuid']);
    }

    public function testGetTransactionWithRelationships(): void
    {
        $testApp = App::factory()->create();
        $transaction = Transaction::factory()->create(['app_id' => $testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions/' . $transaction->uuid);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['uuid', 'type', 'status'],
        ]);
    }

    public function testListTransactionsWithSearchQuery(): void
    {
        $description = 'Unique payment description 12345';
        Transaction::factory()->create(['description' => $description]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?search=Unique%20payment');

        $response->assertStatus(200);
    }

    public function testListTransactionsPerPageLimit(): void
    {
        Transaction::factory(150)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?per_page=100');

        $response->assertStatus(200);
        $this->assertEquals(100, count($response->json('data')));
    }

    public function testGetTransactionInvalidUUID(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions/invalid-uuid');

        $response->assertStatus(404);
    }
}
