<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class PaymentTest extends TenantTestCase
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
            'permissions' => ['payments.create', 'payments.read'],
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

    public function testListPaymentsWithValidCredentials(): void
    {
        Transaction::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['uuid', 'type', 'amount']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function testListPaymentsWithoutCredentials(): void
    {
        $response = $this->getJson('/api/v1/payments');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Authentication credentials are required.');
    }

    public function testListPaymentsWithInvalidAppSecret(): void
    {
        $response = $this->withHeaders([
            'X-App-Id' => $this->app->app_id,
            'X-App-Secret' => 'invalid-secret',
        ])->getJson('/api/v1/payments');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid application credentials.');
    }

    public function testListPaymentsWithInactiveApp(): void
    {
        $this->app->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Application is inactive.');
    }

    public function testListPaymentsWithPagination(): void
    {
        Transaction::factory(25)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments?per_page=10&page=1');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(1, $response->json('meta.current_page'));
    }

    public function testListPaymentsWithStatusFilter(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $failedStatus = TransactionStatus::where('slug', 'failed')->first();

        Transaction::factory(3)->create(['transaction_status_id' => $approvedStatus->id]);
        Transaction::factory(2)->create(['transaction_status_id' => $failedStatus->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments?status=approved');

        $response->assertStatus(200);
    }

    public function testListPaymentsWithPaymentMethodFilter(): void
    {
        $transaction = Transaction::factory()->create([
            'metadata' => ['payment_method' => 'pix'],
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments?payment_method=pix');

        $response->assertStatus(200);
    }

    public function testListPaymentsWithDateFilter(): void
    {
        Transaction::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments?from_date=2024-01-01&to_date=2025-12-31');

        $response->assertStatus(200);
    }

    public function testCreatePaymentWithValidData(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.50,
                'currency' => $this->wallet->currency()->first()->code,
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test payment',
                'metadata' => ['order_id' => '12345'],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['uuid', 'type', 'amount'],
        ]);
    }

    public function testCreatePaymentWithCreditCard(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 250.00,
                'currency' => $this->wallet->currency()->first()->code,
                'payment_method' => 'credit_card',
                'customer' => 'Test Customer',
                'card' => [
                    'number' => '4532015112830366',
                    'holder_name' => 'John Doe',
                    'expiry_month' => '12',
                    'expiry_year' => '2025',
                    'cvv' => '123',
                ],
                'installments' => 3,
                'description' => 'Test credit card payment',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['uuid', 'metadata'],
        ]);
    }

    public function testCreatePaymentWithInvalidCurrency(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.00,
                'currency' => 'XYZ',
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test payment',
            ]);

        $response->assertStatus(422);
    }

    public function testCreatePaymentRequiresAmount(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'currency' => $this->wallet->currency()->first()->code,
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test payment',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function testCreatePaymentRequiresPaymentMethod(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.00,
                'currency' => $this->wallet->currency()->first()->code,
                'customer' => 'Test Customer',
                'description' => 'Test payment',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('payment_method');
    }

    public function testGetPaymentDetails(): void
    {
        $payment = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments/' . $payment->uuid);

        $response->assertStatus(200);
        $response->assertJsonPath('data.uuid', $payment->uuid);
    }

    public function testGetNonExistentPayment(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/payments/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function testCancelPaymentWithValidReason(): void
    {
        $payment = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/cancel', [
                'reason' => 'Customer requested cancellation',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testCancelPaymentRequiresReason(): void
    {
        $payment = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/cancel', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reason');
    }

    public function testCancelPaymentWithShortReason(): void
    {
        $payment = Transaction::factory()->create();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/cancel', [
                'reason' => 'short',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reason');
    }

    public function testRefundPaymentWithValidData(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'transaction_status_id' => $approvedStatus->id,
            'amount' => 100.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/refund', [
                'amount' => 50.00,
                'reason' => 'Customer requested partial refund',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testRefundPaymentFullAmount(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'transaction_status_id' => $approvedStatus->id,
            'amount' => 100.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/refund', [
                'reason' => 'Full refund requested',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['original_payment', 'refund_transaction']]);
    }

    public function testRefundPaymentRequiresReason(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'transaction_status_id' => $approvedStatus->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/refund', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reason');
    }

    public function testCannotCancelFinalizedPayment(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'transaction_status_id' => $approvedStatus->id,
        ]);
        $payment->update(['is_final' => true]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/cancel', [
                'reason' => 'Cannot cancel finalized payment',
            ]);

        $response->assertStatus(422);
    }

    public function testRefundPaymentWithMetadata(): void
    {
        $approvedStatus = TransactionStatus::where('slug', 'approved')->first();
        $payment = Transaction::factory()->create([
            'transaction_status_id' => $approvedStatus->id,
            'amount' => 100.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments/' . $payment->uuid . '/refund', [
                'amount' => 100.00,
                'reason' => 'Refund with metadata',
                'metadata' => ['refund_reason' => 'duplicate_charge'],
            ]);

        $response->assertStatus(200);
    }

    public function testCreatePixPaymentGeneratesQRCode(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/payments', [
                'amount' => 100.50,
                'currency' => $this->wallet->currency()->first()->code,
                'payment_method' => 'pix',
                'customer' => 'Test Customer',
                'description' => 'Test PIX payment',
            ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.metadata.pix_qr_code'));
        $this->assertNotNull($response->json('data.metadata.pix_expiration'));
    }
}
