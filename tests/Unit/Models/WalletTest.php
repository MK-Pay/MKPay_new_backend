<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\Wallet;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TenantTestCase;

class WalletTest extends TenantTestCase
{
    // UUID Auto-generation Tests

    public function testUuidIsAutomaticallyGeneratedOnCreation(): void
    {
        $wallet = Wallet::factory()->create();

        $this->assertNotNull($wallet->uuid);
        $this->assertTrue(Str::isUuid($wallet->uuid));
    }

    public function testUuidCanBeManuallySetOnCreation(): void
    {
        $customUuid = (string) Str::uuid();

        $wallet = Wallet::factory()->create([
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $wallet->uuid);
    }

    // Fillable Attributes Tests

    public function testFillableAttributesCanBeMassAssigned(): void
    {
        $account = Account::factory()->create();
        $app = App::factory()->create();

        $data = [
            'account_id' => $account->id,
            'app_id' => $app->id,
            'is_main' => true,
            'is_active' => true,
        ];

        $wallet = Wallet::create($data);

        $this->assertEquals($account->id, $wallet->account_id);
        $this->assertEquals($app->id, $wallet->app_id);
        $this->assertTrue($wallet->is_main);
        $this->assertTrue($wallet->is_active);
    }

    // Cast Tests

    public function testIsMainIsCastToBoolean(): void
    {
        $wallet = Wallet::factory()->main()->create();

        $this->assertIsBool($wallet->is_main);
        $this->assertTrue($wallet->is_main);
    }

    public function testIsActiveIsCastToBoolean(): void
    {
        $wallet = Wallet::factory()->create([
            'is_active' => true,
        ]);

        $this->assertIsBool($wallet->is_active);
        $this->assertTrue($wallet->is_active);
    }

    // Relationship Tests

    public function testWalletBelongsToAccount(): void
    {
        $account = Account::factory()->create();
        $wallet = Wallet::factory()->create([
            'account_id' => $account->id,
        ]);

        $this->assertInstanceOf(Account::class, $wallet->account);
        $this->assertEquals($account->id, $wallet->account->id);
    }

    public function testWalletBelongsToApp(): void
    {
        $app = App::factory()->create();
        $wallet = Wallet::factory()->create([
            'app_id' => $app->id,
        ]);

        $this->assertInstanceOf(App::class, $wallet->app);
        $this->assertEquals($app->id, $wallet->app->id);
    }

    public function testWalletCanHaveNullApp(): void
    {
        $wallet = Wallet::factory()->noApp()->create();

        $this->assertNull($wallet->app_id);
        $this->assertNull($wallet->app);
    }

    public function testWalletHasManyTransactions(): void
    {
        $wallet = Wallet::factory()->create();
        Transaction::factory()->count(5)->create([
            'wallet_id' => $wallet->id,
        ]);

        $this->assertCount(5, $wallet->transactions);
        $this->assertInstanceOf(Transaction::class, $wallet->transactions->first());
    }

    // SoftDeletes Tests

    public function testWalletCanBeSoftDeleted(): void
    {
        $wallet = Wallet::factory()->create();

        $wallet->delete();

        $this->assertSoftDeleted($wallet);
        $this->assertNotNull($wallet->deleted_at);
    }

    public function testSoftDeletedWalletCanBeRestored(): void
    {
        $wallet = Wallet::factory()->create();
        $wallet->delete();

        $wallet->restore();

        $this->assertNull($wallet->deleted_at);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'deleted_at' => null,
        ]);
    }

    public function testSoftDeletedWalletsAreExcludedFromQueries(): void
    {
        $account = Account::factory()->create();
        Wallet::factory()->create(['account_id' => $account->id, 'is_main' => true]);
        $deletedWallet = Wallet::factory()->create(['account_id' => $account->id, 'is_main' => false]);
        $deletedWallet->delete();

        $wallets = Wallet::all();

        $this->assertCount(1, $wallets);
        $this->assertTrue($wallets->first()->is_main);
    }

    public function testSoftDeletedWalletsCanBeRetrievedWithTrashed(): void
    {
        $account = Account::factory()->create();
        Wallet::factory()->create(['account_id' => $account->id]);
        $deletedWallet = Wallet::factory()->create(['account_id' => $account->id]);
        $deletedWallet->delete();

        $wallets = Wallet::withTrashed()->get();

        $this->assertCount(2, $wallets);
    }

    // ActivityLog Tests

    public function testWalletLogsActivityOnCreation(): void
    {
        $wallet = Wallet::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Wallet::class,
            'subject_id' => $wallet->id,
            'event' => 'created',
        ]);
    }

    public function testWalletLogsActivityOnUpdate(): void
    {
        $wallet = Wallet::factory()->create(['is_active' => true]);

        $wallet->update(['is_active' => false]);

        $activities = Activity::where('subject_type', Wallet::class)
            ->where('subject_id', $wallet->id)
            ->where('event', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());
    }

    public function testWalletOnlyLogsDirtyAttributes(): void
    {
        $wallet = Wallet::factory()->create();

        Activity::where('subject_type', Wallet::class)
            ->where('subject_id', $wallet->id)
            ->delete();

        $wallet->touch();

        $activities = Activity::where('subject_type', Wallet::class)
            ->where('subject_id', $wallet->id)
            ->get();

        $this->assertCount(0, $activities);
    }

    // Business Logic Tests

    public function testWalletCanBeSetAsMainWallet(): void
    {
        $wallet = Wallet::factory()->create(['is_main' => false]);

        $this->assertFalse($wallet->is_main);

        $wallet->update(['is_main' => true]);

        $this->assertTrue($wallet->is_main);
    }

    public function testWalletCanBeActivatedAndDeactivated(): void
    {
        $wallet = Wallet::factory()->inactive()->create();

        $this->assertFalse($wallet->is_active);

        $wallet->update(['is_active' => true]);

        $this->assertTrue($wallet->is_active);
    }

    public function testAccountCanHaveMultipleWallets(): void
    {
        $account = Account::factory()->create();

        Wallet::factory()->main()->create(['account_id' => $account->id]);
        Wallet::factory()->count(2)->create(['account_id' => $account->id, 'is_main' => false]);

        $this->assertCount(3, $account->wallets);
    }

    public function testAppCanHaveMultipleWallets(): void
    {
        $app = App::factory()->create();

        Wallet::factory()->count(3)->create(['app_id' => $app->id]);

        $this->assertCount(3, $app->wallets);
    }

    public function testWalletWithoutAppBelongsOnlyToAccount(): void
    {
        $account = Account::factory()->create();
        $wallet = Wallet::factory()->noApp()->create(['account_id' => $account->id]);

        $this->assertNull($wallet->app_id);
        $this->assertInstanceOf(Account::class, $wallet->account);
        $this->assertEquals($account->id, $wallet->account_id);
    }
}
