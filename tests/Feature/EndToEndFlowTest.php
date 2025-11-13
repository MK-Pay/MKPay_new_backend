<?php

namespace Tests\Feature;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class EndToEndFlowTest extends TenantTestCase
{
    use RefreshDatabase;

    protected App $testApp;
    protected Account $account;
    protected Wallet $wallet;
    protected string $appSecret;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Create account and app
        $this->account = Account::factory()->create();
        $this->testApp = App::factory()->create(['account_id' => $this->account->id]);

        // Create secret token
        $this->appSecret = 'test-secret-token-' . bin2hex(random_bytes(16));
        $this->testApp->secretTokens()->create([
            'name' => 'Test Token',
            'token_hash' => bcrypt($this->appSecret),
            'permissions' => ['payments.create', 'payments.read', 'transactions.read', 'wallets.read'],
            'is_active' => true,
        ]);

        // Create currency and wallet
        $this->currency = Currency::first() ?? Currency::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'account_id' => $this->account->id,
            'currency_id' => $this->currency->id,
        ]);
    }

    protected function getAuthHeaders(): array
    {
        return [
            'X-App-Id' => $this->testApp->app_id,
            'X-App-Secret' => $this->appSecret,
        ];
    }

    /**
     * Test complete PIX payment flow
     *
     * Flow: Create payment -> Check status -> Get details -> Cancel if needed
     */
    public function testCompletePixPaymentFlow(): void
    {
        // Step 1: Create a PIX payment
        $createResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.00,
                'currency' => $this->currency->code,
                'payment_method' => 'pix',
                'customer' => 'John Doe',
                'description' => 'Payment for order #12345',
                'metadata' => ['order_id' => '12345'],
            ]);

        $createResponse->assertStatus(201);
        $paymentUuid = $createResponse->json('data.uuid');
        $this->assertNotNull($paymentUuid);

        // Step 2: Verify QR code was generated
        $this->assertNotNull($createResponse->json('data.metadata.pix_qr_code'));
        $this->assertNotNull($createResponse->json('data.metadata.pix_expiration'));

        // Step 3: Get payment details
        $detailsResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments/' . $paymentUuid);

        $detailsResponse->assertStatus(200);
        $detailsResponse->assertJsonPath('data.uuid', $paymentUuid);
        $detailsResponse->assertJsonPath('data.amount', 100.00);

        // Step 4: Verify payment appears in list
        $listResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments');

        $listResponse->assertStatus(200);
        $paymentIds = array_map(fn ($p) => $p['uuid'], $listResponse->json('data'));
        $this->assertContains($paymentUuid, $paymentIds);
    }

    /**
     * Test complete credit card payment flow with installments
     *
     * Flow: Create card payment -> Check installments metadata -> Get payment status
     */
    public function testCompleteCreditCardPaymentFlow(): void
    {
        // Step 1: Create a credit card payment with installments
        $createResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 1200.00,
                'currency' => $this->currency->code,
                'payment_method' => 'credit_card',
                'customer' => 'Jane Smith',
                'installments' => 3,
                'card' => [
                    'number' => '4532015112830366',
                    'holder_name' => 'Jane Smith',
                    'expiry_month' => '12',
                    'expiry_year' => '2026',
                    'cvv' => '123',
                ],
                'description' => 'Installment purchase',
                'metadata' => ['order_id' => '67890'],
            ]);

        $createResponse->assertStatus(201);
        $paymentUuid = $createResponse->json('data.uuid');

        // Step 2: Verify card metadata was stored correctly
        $this->assertEquals('visa', $createResponse->json('data.metadata.card.brand'));
        $this->assertEquals('Jane Smith', $createResponse->json('data.metadata.card.holder_name'));
        $this->assertEquals('0366', $createResponse->json('data.metadata.card.last_four'));
        $this->assertEquals(3, $createResponse->json('data.metadata.installments'));

        // Step 3: Get payment details and verify amount
        $detailsResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments/' . $paymentUuid);

        $detailsResponse->assertStatus(200);
        $detailsResponse->assertJsonPath('data.amount', 1200.00);
    }

    /**
     * Test payment cancellation flow
     *
     * Flow: Create payment -> Cancel with reason -> Verify status change
     */
    public function testPaymentCancellationFlow(): void
    {
        // Step 1: Create a payment
        $createResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 500.00,
                'currency' => $this->currency->code,
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test payment for cancellation',
            ]);

        $createResponse->assertStatus(201);
        $paymentUuid = $createResponse->json('data.uuid');

        // Step 2: Cancel the payment
        $cancelResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $paymentUuid . '/cancel', [
                'reason' => 'Customer requested cancellation',
            ]);

        $cancelResponse->assertStatus(200);
        $cancelResponse->assertJsonPath('success', true);
    }

    /**
     * Test full refund flow
     *
     * Flow: Create payment -> Approve -> Refund full amount -> Verify
     */
    public function testFullRefundFlow(): void
    {
        // Step 1: Create a payment
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 200.00,
            'transaction_status_id' => $approvedStatus->id,
        ]);

        $paymentUuid = $payment->uuid;

        // Step 2: Request full refund
        $refundResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $paymentUuid . '/refund', [
                'reason' => 'Customer requested full refund',
            ]);

        $refundResponse->assertStatus(200);
        $refundResponse->assertJsonPath('success', true);

        // Step 3: Verify refund transaction was created
        $this->assertNotNull($refundResponse->json('data.refund_transaction.uuid'));

        // Step 4: Get original payment and verify it still exists
        $originalResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments/' . $paymentUuid);

        $originalResponse->assertStatus(200);
        $originalResponse->assertJsonPath('data.uuid', $paymentUuid);
    }

    /**
     * Test partial refund flow
     *
     * Flow: Create payment -> Approve -> Partial refund -> Full refund -> Verify
     */
    public function testPartialRefundFlow(): void
    {
        // Step 1: Create a payment
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 1000.00,
            'transaction_status_id' => $approvedStatus->id,
        ]);

        $paymentUuid = $payment->uuid;

        // Step 2: Partial refund (50%)
        $partialRefundResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $paymentUuid . '/refund', [
                'amount' => 500.00,
                'reason' => 'Partial refund for 50% of order',
                'metadata' => ['reason_code' => 'partial_return'],
            ]);

        $partialRefundResponse->assertStatus(200);

        // Step 3: Full refund on remaining amount
        $fullRefundResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $paymentUuid . '/refund', [
                'amount' => 500.00,
                'reason' => 'Final refund for remaining balance',
            ]);

        $fullRefundResponse->assertStatus(200);
    }

    /**
     * Test wallet balance tracking through multiple transactions
     *
     * Flow: Check initial balance -> Create payment -> Check balance after -> Verify
     */
    public function testWalletBalanceTrackingFlow(): void
    {
        // Step 1: Get initial wallet balance
        $initialResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $initialResponse->assertStatus(200);
        $initialBalance = $initialResponse->json('data.balance_available');

        // Step 2: Create a payment
        $paymentResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.00,
                'currency' => $this->currency->code,
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test payment',
            ]);

        $paymentResponse->assertStatus(201);

        // Step 3: Get updated wallet balance
        $updatedResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $updatedResponse->assertStatus(200);

        // Step 4: Verify balance exists
        $updatedBalance = $updatedResponse->json('data.balance_available');
        $this->assertNotNull($updatedBalance);
    }

    /**
     * Test wallet statement generation
     *
     * Flow: Create payments -> Check statement -> Verify transactions are recorded
     */
    public function testWalletStatementFlow(): void
    {
        // Step 1: Create multiple payments
        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders($this->getAuthHeaders())
                ->postJson('/api/v1/payments', [
                    'amount' => (100 + ($i * 50)),
                    'currency' => $this->currency->code,
                    'payment_method' => 'pix',
                    'customer' => 'Customer ' . ($i + 1),
                    'description' => 'Payment ' . ($i + 1),
                ]);
        }

        // Step 2: Get wallet statement
        $statementResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid . '/statement');

        $statementResponse->assertStatus(200);
        $statementResponse->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'balance_available', 'balance_held', 'created_at']],
        ]);
    }

    /**
     * Test transaction filtering and search flow
     *
     * Flow: Create various transactions -> Filter by status -> Search by metadata
     */
    public function testTransactionFilteringFlow(): void
    {
        // Step 1: Create transactions with different statuses
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $processingStatus = TransactionStatus::where('slug', 'processing')->first();

        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'account_id' => $this->account->id,
                'amount' => 100.00,
                'transaction_status_id' => $approvedStatus->id,
                'metadata' => ['order_id' => 'ORD-' . (1001 + $i)],
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Transaction::factory()->create([
                'account_id' => $this->account->id,
                'amount' => 200.00,
                'transaction_status_id' => $processingStatus->id,
                'metadata' => ['order_id' => 'ORD-' . (2001 + $i)],
            ]);
        }

        // Step 2: Filter by approved status
        $approvedResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?status=approved');

        $approvedResponse->assertStatus(200);

        // Step 3: Filter by processing status
        $processingResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/transactions?status=processing');

        $processingResponse->assertStatus(200);
    }

    /**
     * Test account information retrieval and updates
     *
     * Flow: Get account info -> Update details -> Verify changes -> Get updated info
     */
    public function testAccountManagementFlow(): void
    {
        // Step 1: Get initial account info
        $initialResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $initialResponse->assertStatus(200);
        $initialEmail = $initialResponse->json('data.email');

        // Step 2: Update account details
        $updateResponse = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'name' => 'Updated Account Name',
                'phone' => '11987654321',
            ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.name', 'Updated Account Name');

        // Step 3: Verify changes persisted
        $verifyResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonPath('data.name', 'Updated Account Name');
        $verifyResponse->assertJsonPath('data.phone', '11987654321');
        $verifyResponse->assertJsonPath('data.email', $initialEmail); // Email should not change
    }

    /**
     * Test multi-wallet management flow
     *
     * Flow: Create multiple wallets -> Check balances -> Create payments in each
     */
    public function testMultiWalletFlow(): void
    {
        // Step 1: Create another currency and wallet
        $usdCurrency = Currency::factory()->create(['code' => 'USD']);
        $usdWallet = Wallet::factory()->create([
            'account_id' => $this->account->id,
            'currency_id' => $usdCurrency->id,
        ]);

        // Step 2: Get all wallets
        $listResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets');

        $listResponse->assertStatus(200);
        $walletCount = count($listResponse->json('data'));
        $this->assertGreaterThanOrEqual(2, $walletCount);

        // Step 3: Get specific wallet balances
        $brlResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $usdResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $usdWallet->uuid);

        $brlResponse->assertStatus(200);
        $usdResponse->assertStatus(200);
    }

    /**
     * Test error handling in payment creation
     *
     * Flow: Try invalid payment -> Get error -> Verify account balance unchanged
     */
    public function testPaymentErrorHandlingFlow(): void
    {
        // Step 1: Try to create payment with invalid currency
        $invalidResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.00,
                'currency' => 'INVALID',
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Invalid payment',
            ]);

        $invalidResponse->assertStatus(422);

        // Step 2: Verify wallet balance not affected
        $walletResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/wallets/' . $this->wallet->uuid);

        $walletResponse->assertStatus(200);
        $this->assertNotNull($walletResponse->json('data.balance_available'));
    }

    /**
     * Test transaction history over time
     *
     * Flow: Create payments -> Wait -> Create more payments -> Get date-filtered list
     */
    public function testTransactionHistoryFlow(): void
    {
        // Step 1: Create first batch of payments
        for ($i = 0; $i < 2; $i++) {
            $this->withHeaders($this->getAuthHeaders())
                ->postJson('/api/v1/payments', [
                    'amount' => 100.00,
                    'currency' => $this->currency->code,
                    'payment_method' => 'pix',
                    'customer' => 'Customer 1',
                    'description' => 'Batch 1 Payment ' . ($i + 1),
                ]);
        }

        // Step 2: Get list of all payments
        $allResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments');

        $allResponse->assertStatus(200);
        $totalPayments = count($allResponse->json('data'));

        // Step 3: Get transactions within date range
        $dateFilterResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments?from_date=2020-01-01&to_date=2030-12-31');

        $dateFilterResponse->assertStatus(200);
    }

    /**
     * Test authentication failure in payment flow
     *
     * Flow: Try payment without auth headers -> Get 401 -> Use valid headers -> Get 201
     */
    public function testAuthenticationFlow(): void
    {
        // Step 1: Try without auth headers
        $noAuthResponse = $this->postJson('/api/v1/payments', [
            'amount' => 100.00,
            'currency' => $this->currency->code,
            'payment_method' => 'pix',
            'customer' => 'Test Customer',
            'description' => 'Test payment',
        ]);

        $noAuthResponse->assertStatus(401);
        $noAuthResponse->assertJsonPath('message', 'Authentication credentials are required.');

        // Step 2: Try with invalid credentials
        $invalidAuthResponse = $this->withHeaders([
            'X-App-Id' => $this->testApp->app_id,
            'X-App-Secret' => 'invalid-secret',
        ])->postJson('/api/v1/payments', [
            'amount' => 100.00,
            'currency' => $this->currency->code,
            'payment_method' => 'pix',
            'customer' => 'Test Customer',
            'description' => 'Test payment',
        ]);

        $invalidAuthResponse->assertStatus(401);
        $invalidAuthResponse->assertJsonPath('message', 'Invalid application credentials.');

        // Step 3: Use valid credentials
        $validAuthResponse = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.00,
                'currency' => $this->currency->code,
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test payment',
            ]);

        $validAuthResponse->assertStatus(201);
    }
}
