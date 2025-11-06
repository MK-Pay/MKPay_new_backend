<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\AppSecretToken;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\Wallet;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TenantTestCase;

class AppTest extends TenantTestCase
{
    // UUID Auto-generation Tests

    public function testAppIdIsAutomaticallyGeneratedOnCreation(): void
    {
        $app = App::factory()->create();

        $this->assertNotNull($app->app_id);
        $this->assertTrue(Str::isUuid($app->app_id));
    }

    public function testAppIdCanBeManuallySetOnCreation(): void
    {
        $customUuid = (string) Str::uuid();

        $app = App::factory()->create([
            'app_id' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $app->app_id);
    }

    // Fillable Attributes Tests

    public function testFillableAttributesCanBeMassAssigned(): void
    {
        $account = Account::factory()->create();

        $data = [
            'account_id' => $account->id,
            'name' => 'Test App',
            'description' => 'This is a test app',
            'is_active' => true,
            'settings' => ['webhook_enabled' => true],
        ];

        $app = App::create($data);

        $this->assertEquals('Test App', $app->name);
        $this->assertEquals('This is a test app', $app->description);
        $this->assertTrue($app->is_active);
        $this->assertEquals(['webhook_enabled' => true], $app->settings);
    }

    // Cast Tests

    public function testIsActiveIsCastToBoolean(): void
    {
        $app = App::factory()->create([
            'is_active' => true,
        ]);

        $this->assertIsBool($app->is_active);
        $this->assertTrue($app->is_active);
    }

    public function testSettingsIsCastToArray(): void
    {
        $settings = [
            'webhook_enabled' => true,
            'auto_approve_transactions' => false,
            'max_transaction_amount' => 10000,
        ];

        $app = App::factory()->create([
            'settings' => $settings,
        ]);

        $this->assertIsArray($app->settings);
        $this->assertEquals($settings, $app->settings);
    }

    public function testSettingsCanBeNull(): void
    {
        $app = App::factory()->noSettings()->create();

        $this->assertNull($app->settings);
    }

    // Relationship Tests

    public function testAppBelongsToAccount(): void
    {
        $account = Account::factory()->create();
        $app = App::factory()->create([
            'account_id' => $account->id,
        ]);

        $this->assertInstanceOf(Account::class, $app->account);
        $this->assertEquals($account->id, $app->account->id);
    }

    public function testAppHasManySecretTokens(): void
    {
        $app = App::factory()->create();
        AppSecretToken::factory()->count(3)->create([
            'app_id' => $app->id,
        ]);

        $this->assertCount(3, $app->secretTokens);
        $this->assertInstanceOf(AppSecretToken::class, $app->secretTokens->first());
    }

    public function testAppHasManyWallets(): void
    {
        $app = App::factory()->create();
        Wallet::factory()->count(2)->create([
            'app_id' => $app->id,
        ]);

        $this->assertCount(2, $app->wallets);
        $this->assertInstanceOf(Wallet::class, $app->wallets->first());
    }

    public function testAppHasManyTransactions(): void
    {
        $app = App::factory()->create();
        Transaction::factory()->count(5)->create([
            'app_id' => $app->id,
        ]);

        $this->assertCount(5, $app->transactions);
        $this->assertInstanceOf(Transaction::class, $app->transactions->first());
    }

    // SoftDeletes Tests

    public function testAppCanBeSoftDeleted(): void
    {
        $app = App::factory()->create();

        $app->delete();

        $this->assertSoftDeleted($app);
        $this->assertNotNull($app->deleted_at);
    }

    public function testSoftDeletedAppCanBeRestored(): void
    {
        $app = App::factory()->create();
        $app->delete();

        $app->restore();

        $this->assertNull($app->deleted_at);
        $this->assertDatabaseHas('apps', [
            'id' => $app->id,
            'deleted_at' => null,
        ]);
    }

    public function testSoftDeletedAppsAreExcludedFromQueries(): void
    {
        App::factory()->create(['name' => 'Active App']);
        $deletedApp = App::factory()->create(['name' => 'Deleted App']);
        $deletedApp->delete();

        $apps = App::all();

        $this->assertCount(1, $apps);
        $this->assertEquals('Active App', $apps->first()->name);
    }

    public function testSoftDeletedAppsCanBeRetrievedWithTrashed(): void
    {
        App::factory()->create(['name' => 'Active App']);
        $deletedApp = App::factory()->create(['name' => 'Deleted App']);
        $deletedApp->delete();

        $apps = App::withTrashed()->get();

        $this->assertCount(2, $apps);
    }

    // ActivityLog Tests

    public function testAppLogsActivityOnCreation(): void
    {
        $app = App::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => App::class,
            'subject_id' => $app->id,
            'event' => 'created',
        ]);
    }

    public function testAppLogsActivityOnUpdate(): void
    {
        $app = App::factory()->create(['name' => 'Original Name']);

        $app->update(['name' => 'Updated Name']);

        $activities = Activity::where('subject_type', App::class)
            ->where('subject_id', $app->id)
            ->where('event', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());
    }

    public function testAppOnlyLogsDirtyAttributes(): void
    {
        $app = App::factory()->create(['name' => 'Original Name']);

        Activity::where('subject_type', App::class)
            ->where('subject_id', $app->id)
            ->delete();

        $app->touch();

        $activities = Activity::where('subject_type', App::class)
            ->where('subject_id', $app->id)
            ->get();

        $this->assertCount(0, $activities);
    }

    // Business Logic Tests

    public function testAppCanBeActivatedAndDeactivated(): void
    {
        $app = App::factory()->inactive()->create();

        $this->assertFalse($app->is_active);

        $app->update(['is_active' => true]);

        $this->assertTrue($app->is_active);
    }

    public function testAppSettingsCanBeUpdated(): void
    {
        $app = App::factory()->create([
            'settings' => ['webhook_enabled' => false],
        ]);

        $app->update([
            'settings' => ['webhook_enabled' => true, 'new_setting' => 'value'],
        ]);

        $this->assertTrue($app->settings['webhook_enabled']);
        $this->assertEquals('value', $app->settings['new_setting']);
    }

    public function testAppCanHaveMultipleAttributesSet(): void
    {
        $account = Account::factory()->create();

        $app = App::factory()->create([
            'account_id' => $account->id,
            'name' => 'Multi-Feature App',
            'description' => 'App with multiple features',
            'is_active' => true,
            'settings' => [
                'webhook_enabled' => true,
                'auto_approve_transactions' => true,
                'max_daily_transactions' => 1000,
            ],
        ]);

        $this->assertEquals('Multi-Feature App', $app->name);
        $this->assertTrue($app->is_active);
        $this->assertCount(3, $app->settings);
    }
}
