<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TenantTestCase;

class TransactionTest extends TenantTestCase
{
    // UUID Auto-generation Tests

    public function testUuidIsAutomaticallyGeneratedOnCreation(): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertNotNull($transaction->uuid);
        $this->assertTrue(Str::isUuid($transaction->uuid));
    }

    public function testUuidCanBeManuallySetOnCreation(): void
    {
        $customUuid = (string) Str::uuid();

        $transaction = Transaction::factory()->create([
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $transaction->uuid);
    }

    // Fillable Attributes Tests

    public function testFillableAttributesCanBeMassAssigned(): void
    {
        $account = Account::factory()->create();
        $app = App::factory()->create();
        $wallet = Wallet::factory()->create();
        $currency = Currency::factory()->create();
        $status = TransactionStatus::factory()->create();

        $data = [
            'account_id' => $account->id,
            'app_id' => $app->id,
            'wallet_id' => $wallet->id,
            'currency_id' => $currency->id,
            'transaction_status_id' => $status->id,
            'type' => 'credit',
            'amount' => '100.00',
            'fee' => '2.90',
            'net_amount' => '97.10',
            'payment_method' => 'credit_card',
            'external_id' => 'ext-123',
            'description' => 'Test transaction',
            'metadata' => ['key' => 'value'],
            'related_transaction_id' => null,
            'completed_at' => now(),
        ];

        $transaction = Transaction::create($data);

        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals('100.00000000', $transaction->amount);
        $this->assertEquals('2.90000000', $transaction->fee);
        $this->assertEquals('97.10000000', $transaction->net_amount);
        $this->assertEquals('credit_card', $transaction->payment_method);
        $this->assertEquals(['key' => 'value'], $transaction->metadata);
    }

    // Cast Tests

    public function testAmountIsCastToDecimal(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => '1234.56789012',
        ]);

        $this->assertEquals('1234.56789012', $transaction->amount);
    }

    public function testFeeIsCastToDecimal(): void
    {
        $transaction = Transaction::factory()->create([
            'fee' => '35.78901234',
        ]);

        $this->assertEquals('35.78901234', $transaction->fee);
    }

    public function testNetAmountIsCastToDecimal(): void
    {
        $transaction = Transaction::factory()->create([
            'net_amount' => '964.21098766',
        ]);

        $this->assertEquals('964.21098766', $transaction->net_amount);
    }

    public function testMetadataIsCastToArray(): void
    {
        $metadata = [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'device' => 'mobile',
        ];

        $transaction = Transaction::factory()->create([
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals($metadata, $transaction->metadata);
    }

    public function testMetadataCanBeNull(): void
    {
        $transaction = Transaction::factory()->noMetadata()->create();

        $this->assertNull($transaction->metadata);
    }

    public function testCompletedAtIsCastToDatetime(): void
    {
        $transaction = Transaction::factory()->create([
            'completed_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->completed_at);
    }

    // Relationship Tests

    public function testTransactionBelongsToAccount(): void
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
        ]);

        $this->assertInstanceOf(Account::class, $transaction->account);
        $this->assertEquals($account->id, $transaction->account->id);
    }

    public function testTransactionBelongsToApp(): void
    {
        $app = App::factory()->create();
        $transaction = Transaction::factory()->create([
            'app_id' => $app->id,
        ]);

        $this->assertInstanceOf(App::class, $transaction->app);
        $this->assertEquals($app->id, $transaction->app->id);
    }

    public function testTransactionCanHaveNullApp(): void
    {
        $transaction = Transaction::factory()->noApp()->create();

        $this->assertNull($transaction->app_id);
        $this->assertNull($transaction->app);
    }

    public function testTransactionBelongsToWallet(): void
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'wallet_id' => $wallet->id,
        ]);

        $this->assertInstanceOf(Wallet::class, $transaction->wallet);
        $this->assertEquals($wallet->id, $transaction->wallet->id);
    }

    public function testTransactionBelongsToCurrency(): void
    {
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create([
            'currency_id' => $currency->id,
        ]);

        $this->assertInstanceOf(Currency::class, $transaction->currency);
        $this->assertEquals($currency->id, $transaction->currency->id);
    }

    public function testTransactionBelongsToTransactionStatus(): void
    {
        $status = TransactionStatus::factory()->create();
        $transaction = Transaction::factory()->create([
            'transaction_status_id' => $status->id,
        ]);

        $this->assertInstanceOf(TransactionStatus::class, $transaction->transactionStatus);
        $this->assertEquals($status->id, $transaction->transactionStatus->id);
    }

    public function testTransactionBelongsToRelatedTransaction(): void
    {
        $relatedTransaction = Transaction::factory()->create();
        $transaction = Transaction::factory()->create([
            'related_transaction_id' => $relatedTransaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $transaction->relatedTransaction);
        $this->assertEquals($relatedTransaction->id, $transaction->relatedTransaction->id);
    }

    public function testTransactionHasManyRelatedTransactions(): void
    {
        $parentTransaction = Transaction::factory()->create();
        Transaction::factory()->count(3)->create([
            'related_transaction_id' => $parentTransaction->id,
        ]);

        $this->assertCount(3, $parentTransaction->relatedTransactions);
        $this->assertInstanceOf(Transaction::class, $parentTransaction->relatedTransactions->first());
    }

    // SoftDeletes Tests

    public function testTransactionCanBeSoftDeleted(): void
    {
        $transaction = Transaction::factory()->create();

        $transaction->delete();

        $this->assertSoftDeleted($transaction);
        $this->assertNotNull($transaction->deleted_at);
    }

    public function testSoftDeletedTransactionCanBeRestored(): void
    {
        $transaction = Transaction::factory()->create();
        $transaction->delete();

        $transaction->restore();

        $this->assertNull($transaction->deleted_at);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'deleted_at' => null,
        ]);
    }

    public function testSoftDeletedTransactionsAreExcludedFromQueries(): void
    {
        Transaction::factory()->create(['type' => 'credit']);
        $deletedTransaction = Transaction::factory()->create(['type' => 'debit']);
        $deletedTransaction->delete();

        $transactions = Transaction::all();

        $this->assertCount(1, $transactions);
        $this->assertEquals('credit', $transactions->first()->type);
    }

    public function testSoftDeletedTransactionsCanBeRetrievedWithTrashed(): void
    {
        Transaction::factory()->create(['type' => 'credit']);
        $deletedTransaction = Transaction::factory()->create(['type' => 'debit']);
        $deletedTransaction->delete();

        $transactions = Transaction::withTrashed()->get();

        $this->assertCount(2, $transactions);
    }

    // ActivityLog Tests

    public function testTransactionLogsActivityOnCreation(): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertDatabaseHas(config('activitylog.table_name'), [
            'subject_type' => Transaction::class,
            'subject_id' => $transaction->id,
            'event' => 'created',
        ]);
    }

    public function testTransactionLogsActivityOnUpdate(): void
    {
        $transaction = Transaction::factory()->create(['description' => 'Original']);

        $transaction->update(['description' => 'Updated']);

        $activities = Activity::where('subject_type', Transaction::class)
            ->where('subject_id', $transaction->id)
            ->where('event', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());
    }

    public function testTransactionOnlyLogsDirtyAttributes(): void
    {
        $transaction = Transaction::factory()->create();

        Activity::where('subject_type', Transaction::class)
            ->where('subject_id', $transaction->id)
            ->delete();

        $transaction->touch();

        $activities = Activity::where('subject_type', Transaction::class)
            ->where('subject_id', $transaction->id)
            ->get();

        $this->assertCount(0, $activities);
    }

    // Business Logic Tests

    public function testTransactionCanBeCreditType(): void
    {
        $transaction = Transaction::factory()->credit()->create();

        $this->assertEquals('credit', $transaction->type);
    }

    public function testTransactionCanBeDebitType(): void
    {
        $transaction = Transaction::factory()->debit()->create();

        $this->assertEquals('debit', $transaction->type);
    }

    public function testTransactionCanBeTransferType(): void
    {
        $transaction = Transaction::factory()->transfer()->create();

        $this->assertEquals('transfer', $transaction->type);
    }

    public function testTransactionCanBePending(): void
    {
        $transaction = Transaction::factory()->pending()->create();

        $this->assertNull($transaction->completed_at);
    }

    public function testTransactionCanBeCompleted(): void
    {
        $transaction = Transaction::factory()->create([
            'completed_at' => now(),
        ]);

        $this->assertNotNull($transaction->completed_at);
    }

    public function testTransactionCalculatesNetAmountCorrectly(): void
    {
        $amount = 1000.00;
        $fee = 29.00;
        $netAmount = $amount - $fee;

        $transaction = Transaction::factory()->create([
            'amount' => (string) $amount,
            'fee' => (string) $fee,
            'net_amount' => (string) $netAmount,
        ]);

        $this->assertEquals('1000.00000000', $transaction->amount);
        $this->assertEquals('29.00000000', $transaction->fee);
        $this->assertEquals('971.00000000', $transaction->net_amount);
    }

    public function testTransactionCanHaveRelatedTransactionsForRefunds(): void
    {
        $originalTransaction = Transaction::factory()->credit()->create([
            'amount' => '1000.00',
        ]);

        $refundTransaction = Transaction::factory()->debit()->create([
            'amount' => '1000.00',
            'related_transaction_id' => $originalTransaction->id,
        ]);

        $this->assertEquals($originalTransaction->id, $refundTransaction->related_transaction_id);
        $this->assertCount(1, $originalTransaction->relatedTransactions);
    }

    public function testTransactionCanHaveExternalId(): void
    {
        $externalId = 'stripe_pi_123456789';

        $transaction = Transaction::factory()->create([
            'external_id' => $externalId,
        ]);

        $this->assertEquals($externalId, $transaction->external_id);
    }

    public function testTransactionMetadataStoresAdditionalInformation(): void
    {
        $metadata = [
            'customer_id' => 'cus_123',
            'invoice_id' => 'inv_456',
            'payment_intent' => 'pi_789',
        ];

        $transaction = Transaction::factory()->create([
            'metadata' => $metadata,
        ]);

        $this->assertEquals('cus_123', $transaction->metadata['customer_id']);
        $this->assertEquals('inv_456', $transaction->metadata['invoice_id']);
        $this->assertEquals('pi_789', $transaction->metadata['payment_intent']);
    }
}
