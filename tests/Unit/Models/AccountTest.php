<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Account;
use App\Models\Tenant\AccountCategory;
use App\Models\Tenant\AccountStatus;
use App\Models\Tenant\AccountType;
use App\Models\Tenant\App;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\Wallet;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TenantTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AccountTest extends TenantTestCase
{
    use DatabaseMigrations;
    // UUID Auto-generation Tests

    public function testUuidIsAutomaticallyGeneratedOnCreation(): void
    {
        $account = Account::factory()->create();

        $this->assertNotNull($account->uuid);
        $this->assertTrue(Str::isUuid($account->uuid));
    }

    public function testUuidCanBeManuallySetOnCreation(): void
    {
        $customUuid = (string) Str::uuid();

        $account = Account::factory()->create([
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $account->uuid);
    }

    // Fillable Attributes Tests

    public function testFillableAttributesCanBeMassAssigned(): void
    {
        $accountType = AccountType::factory()->create();
        $accountCategory = AccountCategory::factory()->create();
        $accountStatus = AccountStatus::factory()->create();

        $data = [
            'account_type_id' => $accountType->id,
            'account_category_id' => $accountCategory->id,
            'account_status_id' => $accountStatus->id,
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'name' => 'Test Account',
            'cpf' => '12345678901',
            'cnpj' => null,
            'phone' => '+5511999999999',
            'usage_types' => ['payment', 'transfer'],
            'hourly_transaction_limit' => '5000.00',
            'daily_transaction_limit' => '20000.00',
            'verified_at' => now(),
        ];

        $account = Account::create($data);

        $this->assertEquals('test@example.com', $account->email);
        $this->assertEquals('Test Account', $account->name);
        $this->assertEquals('12345678901', $account->cpf);
        $this->assertEquals('+5511999999999', $account->phone);
        $this->assertEquals(['payment', 'transfer'], $account->usage_types);
    }

    // Cast Tests

    public function testEmailVerifiedAtIsCastToDatetime(): void
    {
        $account = Account::factory()->create([
            'email_verified_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->email_verified_at);
    }

    public function testVerifiedAtIsCastToDatetime(): void
    {
        $account = Account::factory()->create([
            'verified_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->verified_at);
    }

    public function testUsageTypesIsCastToArray(): void
    {
        $account = Account::factory()->create([
            'usage_types' => ['payment', 'transfer', 'withdrawal'],
        ]);

        $this->assertIsArray($account->usage_types);
        $this->assertEquals(['payment', 'transfer', 'withdrawal'], $account->usage_types);
    }

    public function testHourlyTransactionLimitIsCastToDecimal(): void
    {
        $account = Account::factory()->create([
            'hourly_transaction_limit' => '10000.50',
        ]);

        $this->assertEquals('10000.50', $account->hourly_transaction_limit);
    }

    public function testDailyTransactionLimitIsCastToDecimal(): void
    {
        $account = Account::factory()->create([
            'daily_transaction_limit' => '50000.75',
        ]);

        $this->assertEquals('50000.75', $account->daily_transaction_limit);
    }

    // Relationship Tests

    public function testAccountBelongsToAccountType(): void
    {
        $accountType = AccountType::factory()->create();
        $account = Account::factory()->create([
            'account_type_id' => $accountType->id,
        ]);

        $this->assertInstanceOf(AccountType::class, $account->accountType);
        $this->assertEquals($accountType->id, $account->accountType->id);
    }

    public function testAccountBelongsToAccountCategory(): void
    {
        $accountCategory = AccountCategory::factory()->create();
        $account = Account::factory()->create([
            'account_category_id' => $accountCategory->id,
        ]);

        $this->assertInstanceOf(AccountCategory::class, $account->accountCategory);
        $this->assertEquals($accountCategory->id, $account->accountCategory->id);
    }

    public function testAccountCanHaveNullAccountCategory(): void
    {
        $account = Account::factory()->noCategory()->create();

        $this->assertNull($account->account_category_id);
        $this->assertNull($account->accountCategory);
    }

    public function testAccountBelongsToAccountStatus(): void
    {
        $accountStatus = AccountStatus::factory()->create();
        $account = Account::factory()->create([
            'account_status_id' => $accountStatus->id,
        ]);

        $this->assertInstanceOf(AccountStatus::class, $account->accountStatus);
        $this->assertEquals($accountStatus->id, $account->accountStatus->id);
    }

    public function testAccountHasManyApps(): void
    {
        $account = Account::factory()->create();
        App::factory()->count(3)->create([
            'account_id' => $account->id,
        ]);

        $this->assertCount(3, $account->apps);
        $this->assertInstanceOf(App::class, $account->apps->first());
    }

    public function testAccountHasManyWallets(): void
    {
        $account = Account::factory()->create();
        Wallet::factory()->count(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertCount(2, $account->wallets);
        $this->assertInstanceOf(Wallet::class, $account->wallets->first());
    }

    public function testAccountHasManyTransactions(): void
    {
        $account = Account::factory()->create();
        Transaction::factory()->count(5)->create([
            'account_id' => $account->id,
        ]);

        $this->assertCount(5, $account->transactions);
        $this->assertInstanceOf(Transaction::class, $account->transactions->first());
    }

    // SoftDeletes Tests

    public function testAccountCanBeSoftDeleted(): void
    {
        $account = Account::factory()->create();

        $account->delete();

        $this->assertSoftDeleted($account);
        $this->assertNotNull($account->deleted_at);
    }

    public function testSoftDeletedAccountCanBeRestored(): void
    {
        $account = Account::factory()->create();
        $account->delete();

        $account->restore();

        $this->assertNull($account->deleted_at);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }

    public function testSoftDeletedAccountsAreExcludedFromQueries(): void
    {
        Account::factory()->create(['email' => 'active@example.com']);
        $deletedAccount = Account::factory()->create(['email' => 'deleted@example.com']);
        $deletedAccount->delete();

        $accounts = Account::all();

        $this->assertCount(1, $accounts);
        $this->assertEquals('active@example.com', $accounts->first()->email);
    }

    public function testSoftDeletedAccountsCanBeRetrievedWithTrashed(): void
    {
        Account::factory()->create(['email' => 'active@example.com']);
        $deletedAccount = Account::factory()->create(['email' => 'deleted@example.com']);
        $deletedAccount->delete();

        $accounts = Account::withTrashed()->get();

        $this->assertCount(2, $accounts);
    }

    // ActivityLog Tests

    public function testAccountLogsActivityOnCreation(): void
    {
        $account = Account::factory()->create();

        $this->assertDatabaseHas(config('activitylog.table_name'), [
            'subject_type' => Account::class,
            'subject_id' => $account->id,
            'event' => 'created',
        ]);
    }

    public function testAccountLogsActivityOnUpdate(): void
    {
        $account = Account::factory()->create(['name' => 'Original Name']);

        $account->update(['name' => 'Updated Name']);

        $activities = Activity::where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->where('event', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());
    }

    public function testAccountOnlyLogsDirtyAttributes(): void
    {
        $account = Account::factory()->create(['name' => 'Original Name']);

        Activity::where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->delete();

        $account->touch();

        $activities = Activity::where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->get();

        $this->assertCount(0, $activities);
    }

    // Business Logic Tests

    public function testAccountCanBeVerified(): void
    {
        $account = Account::factory()->unverified()->create();

        $this->assertNull($account->email_verified_at);
        $this->assertNull($account->verified_at);

        $account->update([
            'email_verified_at' => now(),
            'verified_at' => now(),
        ]);

        $this->assertNotNull($account->email_verified_at);
        $this->assertNotNull($account->verified_at);
    }

    public function testAccountCanHaveBothCpfAndCnpjNull(): void
    {
        $account = Account::factory()->create([
            'cpf' => null,
            'cnpj' => null,
        ]);

        $this->assertNull($account->cpf);
        $this->assertNull($account->cnpj);
    }

    public function testAccountCanHaveTransactionLimits(): void
    {
        $account = Account::factory()->create([
            'hourly_transaction_limit' => '5000.00',
            'daily_transaction_limit' => '25000.00',
        ]);

        $this->assertEquals('5000.00', $account->hourly_transaction_limit);
        $this->assertEquals('25000.00', $account->daily_transaction_limit);
    }

    public function testAccountCanHaveNullTransactionLimits(): void
    {
        $account = Account::factory()->noLimits()->create();

        $this->assertNull($account->hourly_transaction_limit);
        $this->assertNull($account->daily_transaction_limit);
    }
}
