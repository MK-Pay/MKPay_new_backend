<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant\Account;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class TransactionTest extends TenantTestCase
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

    public function testListTransactions(): void
    {
        Transaction::factory(5)->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uuid', 'account_uuid', 'amount', 'status',
                    'transaction_type', 'created_at',
                ],
            ],
            'meta' => ['total'],
        ]);
    }

    public function testListTransactionsWithFilters(): void
    {
        $account = Account::factory()->create();
        Transaction::factory(3)->create(['account_uuid' => $account->uuid]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/transactions?account_uuid=' . $account->uuid);

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    public function testGetTransactionDetails(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/transactions/' . $transaction->uuid);

        $response->assertStatus(200);
        $response->assertJsonPath('data.uuid', $transaction->uuid);
        $response->assertJsonStructure([
            'data' => [
                'uuid', 'account_uuid', 'amount', 'fee', 'net_amount',
                'transaction_type', 'transaction_status', 'created_at',
            ],
        ]);
    }

    public function testApproveTransaction(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/transactions/' . $transaction->uuid . '/approve');

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('approved', $transaction->transactionStatus->slug);
    }

    public function testRejectTransaction(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/transactions/' . $transaction->uuid . '/reject');

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->transactionStatus->slug);
    }

    public function testRefundTransaction(): void
    {
        $walletTo = Wallet::factory()->create(['balance_available' => 1000]);
        $transaction = Transaction::factory()->create([
            'wallet_to_uuid' => $walletTo->uuid,
            'amount' => 100,
            'net_amount' => 95,
        ]);

        // First approve the transaction
        $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/transactions/' . $transaction->uuid . '/approve');

        // Then refund it
        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/transactions/' . $transaction->uuid . '/refund', [
                'amount' => 100,
                'reason' => 'Testing refund',
            ]);

        $response->assertStatus(200);
    }

    public function testCannotRefundPendingTransaction(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/transactions/' . $transaction->uuid . '/refund', [
                'amount' => 100,
                'reason' => 'Testing refund',
            ]);

        $response->assertStatus(422);
    }

    public function testUpdateTransactionStatus(): void
    {
        $transaction = Transaction::factory()->create();
        $status = TransactionStatus::where('slug', 'processing')->first();

        $response = $this->withHeaders($this->getAuthHeader())
            ->putJson('/admin/transactions/' . $transaction->uuid, [
                'status_id' => $status->id,
            ]);

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('processing', $transaction->transactionStatus->slug);
    }

    public function testDeleteTransaction(): void
    {
        $transaction = Transaction::factory()->create();
        $uuid = $transaction->uuid;

        $response = $this->withHeaders($this->getAuthHeader())
            ->deleteJson('/admin/transactions/' . $uuid);

        $response->assertStatus(200);

        // Transaction should be soft deleted
        $this->assertSoftDeleted('transactions', ['uuid' => $uuid]);
    }

    public function testTransactionWithMetadata(): void
    {
        $metadata = [
            'order_id' => '12345',
            'customer_id' => 'cust_789',
            'payment_method' => 'credit_card',
        ];

        $transaction = Transaction::factory()->create([
            'metadata' => $metadata,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/transactions/' . $transaction->uuid);

        $response->assertStatus(200);
        // Metadata should be in the response
        $this->assertNotEmpty($response->json('data.metadata'));
    }

    public function testListTransactionsByStatus(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $failedStatus = TransactionStatus::where('slug', 'failed')->first();

        Transaction::factory(3)->create(['transaction_status_id' => $approvedStatus->id]);
        Transaction::factory(2)->create(['transaction_status_id' => $failedStatus->id]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/transactions?status=' . $approvedStatus->slug);

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }
}
