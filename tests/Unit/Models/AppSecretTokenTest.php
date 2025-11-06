<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\App;
use App\Models\Tenant\AppSecretToken;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TenantTestCase;

class AppSecretTokenTest extends TenantTestCase
{
    // Fillable Attributes Tests

    public function testFillableAttributesCanBeMassAssigned(): void
    {
        $app = App::factory()->create();

        $data = [
            'app_id' => $app->id,
            'name' => 'Production Token',
            'permissions' => ['read', 'write'],
            'is_active' => true,
            'last_used_at' => now(),
            'expires_at' => now()->addYear(),
        ];

        $token = AppSecretToken::create($data);

        $this->assertEquals('Production Token', $token->name);
        $this->assertEquals(['read', 'write'], $token->permissions);
        $this->assertTrue($token->is_active);
    }

    // Cast Tests

    public function testPermissionsIsCastToArray(): void
    {
        $permissions = ['read', 'write', 'delete', 'admin'];

        $token = AppSecretToken::factory()->create([
            'permissions' => $permissions,
        ]);

        $this->assertIsArray($token->permissions);
        $this->assertEquals($permissions, $token->permissions);
    }

    public function testIsActiveIsCastToBoolean(): void
    {
        $token = AppSecretToken::factory()->create([
            'is_active' => true,
        ]);

        $this->assertIsBool($token->is_active);
        $this->assertTrue($token->is_active);
    }

    public function testLastUsedAtIsCastToDatetime(): void
    {
        $token = AppSecretToken::factory()->used()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $token->last_used_at);
    }

    public function testExpiresAtIsCastToDatetime(): void
    {
        $token = AppSecretToken::factory()->create([
            'expires_at' => '2025-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $token->expires_at);
    }

    public function testExpiresAtCanBeNull(): void
    {
        $token = AppSecretToken::factory()->noExpiration()->create();

        $this->assertNull($token->expires_at);
    }

    // Relationship Tests

    public function testAppSecretTokenBelongsToApp(): void
    {
        $app = App::factory()->create();
        $token = AppSecretToken::factory()->create([
            'app_id' => $app->id,
        ]);

        $this->assertInstanceOf(App::class, $token->app);
        $this->assertEquals($app->id, $token->app->id);
    }

    // Hidden Attributes Tests

    public function testTokenHashIsHiddenFromSerialization(): void
    {
        $token = AppSecretToken::factory()->create();

        $array = $token->toArray();

        $this->assertArrayNotHasKey('token_hash', $array);
    }

    public function testTokenHashIsAccessibleDirectly(): void
    {
        $token = AppSecretToken::factory()->create();

        $this->assertNotNull($token->token_hash);
        $this->assertIsString($token->token_hash);
    }

    // SoftDeletes Tests

    public function testAppSecretTokenCanBeSoftDeleted(): void
    {
        $token = AppSecretToken::factory()->create();

        $token->delete();

        $this->assertSoftDeleted($token);
        $this->assertNotNull($token->deleted_at);
    }

    public function testSoftDeletedAppSecretTokenCanBeRestored(): void
    {
        $token = AppSecretToken::factory()->create();
        $token->delete();

        $token->restore();

        $this->assertNull($token->deleted_at);
        $this->assertDatabaseHas('app_secret_tokens', [
            'id' => $token->id,
            'deleted_at' => null,
        ]);
    }

    public function testSoftDeletedAppSecretTokensAreExcludedFromQueries(): void
    {
        AppSecretToken::factory()->create(['name' => 'Active Token']);
        $deletedToken = AppSecretToken::factory()->create(['name' => 'Deleted Token']);
        $deletedToken->delete();

        $tokens = AppSecretToken::all();

        $this->assertCount(1, $tokens);
        $this->assertEquals('Active Token', $tokens->first()->name);
    }

    public function testSoftDeletedAppSecretTokensCanBeRetrievedWithTrashed(): void
    {
        AppSecretToken::factory()->create(['name' => 'Active Token']);
        $deletedToken = AppSecretToken::factory()->create(['name' => 'Deleted Token']);
        $deletedToken->delete();

        $tokens = AppSecretToken::withTrashed()->get();

        $this->assertCount(2, $tokens);
    }

    // ActivityLog Tests

    public function testAppSecretTokenLogsActivityOnCreation(): void
    {
        $token = AppSecretToken::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => AppSecretToken::class,
            'subject_id' => $token->id,
            'event' => 'created',
        ]);
    }

    public function testAppSecretTokenLogsActivityOnUpdate(): void
    {
        $token = AppSecretToken::factory()->create(['name' => 'Original Name']);

        $token->update(['name' => 'Updated Name']);

        $activities = Activity::where('subject_type', AppSecretToken::class)
            ->where('subject_id', $token->id)
            ->where('event', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());
    }

    public function testAppSecretTokenOnlyLogsDirtyAttributes(): void
    {
        $token = AppSecretToken::factory()->create();

        Activity::where('subject_type', AppSecretToken::class)
            ->where('subject_id', $token->id)
            ->delete();

        $token->touch();

        $activities = Activity::where('subject_type', AppSecretToken::class)
            ->where('subject_id', $token->id)
            ->get();

        $this->assertCount(0, $activities);
    }

    // Business Logic Tests

    public function testAppSecretTokenCanBeActivatedAndDeactivated(): void
    {
        $token = AppSecretToken::factory()->inactive()->create();

        $this->assertFalse($token->is_active);

        $token->update(['is_active' => true]);

        $this->assertTrue($token->is_active);
    }

    public function testAppSecretTokenCanTrackLastUsed(): void
    {
        $token = AppSecretToken::factory()->create([
            'last_used_at' => null,
        ]);

        $this->assertNull($token->last_used_at);

        $now = now();
        $token->update(['last_used_at' => $now]);

        $this->assertNotNull($token->last_used_at);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $token->last_used_at->format('Y-m-d H:i:s'));
    }

    public function testAppSecretTokenCanHaveExpiration(): void
    {
        $expiryDate = now()->addMonths(6);

        $token = AppSecretToken::factory()->create([
            'expires_at' => $expiryDate,
        ]);

        $this->assertEquals($expiryDate->format('Y-m-d H:i:s'), $token->expires_at->format('Y-m-d H:i:s'));
    }

    public function testAppSecretTokenCanBeExpired(): void
    {
        $token = AppSecretToken::factory()->expired()->create();

        $this->assertTrue($token->expires_at->isPast());
    }

    public function testAppSecretTokenCanHaveDifferentPermissions(): void
    {
        $readOnlyToken = AppSecretToken::factory()->readOnly()->create();
        $fullAccessToken = AppSecretToken::factory()->fullPermissions()->create();

        $this->assertEquals(['read'], $readOnlyToken->permissions);
        $this->assertEquals(['read', 'write', 'delete', 'admin'], $fullAccessToken->permissions);
    }

    public function testAppSecretTokenHashIsEncrypted(): void
    {
        $plainToken = 'my-secret-token-123';
        $hashedToken = Hash::make($plainToken);

        $token = AppSecretToken::factory()->create([
            'token_hash' => $hashedToken,
        ]);

        $this->assertNotEquals($plainToken, $token->token_hash);
        $this->assertTrue(Hash::check($plainToken, $token->token_hash));
    }

    public function testAppCanHaveMultipleSecretTokens(): void
    {
        $app = App::factory()->create();

        AppSecretToken::factory()->count(3)->create([
            'app_id' => $app->id,
        ]);

        $this->assertCount(3, $app->secretTokens);
    }

    public function testAppSecretTokenPermissionsCanBeUpdated(): void
    {
        $token = AppSecretToken::factory()->readOnly()->create();

        $this->assertEquals(['read'], $token->permissions);

        $token->update(['permissions' => ['read', 'write']]);

        $this->assertEquals(['read', 'write'], $token->permissions);
    }

    public function testInactiveTokensCanBeQueried(): void
    {
        AppSecretToken::factory()->create(['is_active' => true]);
        AppSecretToken::factory()->create(['is_active' => true]);
        AppSecretToken::factory()->inactive()->create();

        $activeTokens = AppSecretToken::where('is_active', true)->get();
        $inactiveTokens = AppSecretToken::where('is_active', false)->get();

        $this->assertCount(2, $activeTokens);
        $this->assertCount(1, $inactiveTokens);
    }

    public function testExpiredTokensCanBeQueried(): void
    {
        AppSecretToken::factory()->create(['expires_at' => now()->addYear()]);
        AppSecretToken::factory()->create(['expires_at' => now()->addMonths(6)]);
        AppSecretToken::factory()->expired()->create();

        $expiredTokens = AppSecretToken::where('expires_at', '<', now())->get();

        $this->assertCount(1, $expiredTokens);
    }
}
