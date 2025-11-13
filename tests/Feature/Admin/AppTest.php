<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class AppTest extends TenantTestCase
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

    public function testListApps(): void
    {
        App::factory(3)->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/apps');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'app_id', 'name', 'account', 'tokens_count'],
            ],
            'meta' => ['total'],
        ]);
    }

    public function testGetAppsByAccount(): void
    {
        $account = Account::factory()->create();
        App::factory(2)->create(['account_uuid' => $account->uuid]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/accounts/' . $account->uuid . '/apps');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    public function testCreateApp(): void
    {
        $account = Account::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/apps', [
                'account_uuid' => $account->uuid,
                'name' => 'Test Application',
                'description' => 'Test app description',
                'webhook_url' => 'https://example.com/webhook',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'app_id', 'name', 'account_uuid'],
        ]);
        $this->assertNotNull($response->json('data.app_id'));
    }

    public function testGetAppDetails(): void
    {
        $app = App::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/apps/' . $app->app_id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.app_id', $app->app_id);
    }

    public function testUpdateApp(): void
    {
        $app = App::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->putJson('/admin/apps/' . $app->app_id, [
                'name' => 'Updated App Name',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated App Name', $response->json('data.name'));
    }

    public function testActivateApp(): void
    {
        $app = App::factory()->create(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/apps/' . $app->app_id . '/activate');

        $response->assertStatus(200);
        $app->refresh();
        $this->assertTrue($app->is_active);
    }

    public function testDeactivateApp(): void
    {
        $app = App::factory()->create(['is_active' => true]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/apps/' . $app->app_id . '/deactivate');

        $response->assertStatus(200);
        $app->refresh();
        $this->assertFalse($app->is_active);
    }

    public function testDeleteApp(): void
    {
        $app = App::factory()->create();
        $appId = $app->app_id;

        $response = $this->withHeaders($this->getAuthHeader())
            ->deleteJson('/admin/apps/' . $appId);

        $response->assertStatus(200);

        // Verify soft delete
        $this->assertSoftDeleted('apps', ['app_id' => $appId]);
    }

    public function testListAppTokens(): void
    {
        $app = App::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->getJson('/admin/apps/' . $app->app_id . '/tokens');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'app_id', 'name', 'permissions', 'is_active'],
            ],
        ]);
    }

    public function testCreateAppToken(): void
    {
        $app = App::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/apps/' . $app->app_id . '/tokens', [
                'name' => 'Test Token',
                'permissions' => ['payments.create', 'transactions.read'],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'app_id', 'name', 'permissions'],
            'token' => [], // Token should be returned only on creation
        ]);
    }

    public function testRevokeAppToken(): void
    {
        $app = App::factory()->create();
        $token = $app->appSecretTokens()->create([
            'name' => 'Test Token',
            'token_hash' => hash('sha256', 'test-token'),
            'permissions' => ['payments.create'],
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->getAuthHeader())
            ->deleteJson('/admin/apps/' . $app->app_id . '/tokens/' . $token->id);

        $response->assertStatus(200);
        $token->refresh();
        $this->assertFalse($token->is_active);
    }

    public function testCreateAppRequiresValidAccount(): void
    {
        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/apps', [
                'account_uuid' => '00000000-0000-0000-0000-000000000000',
                'name' => 'Test Application',
            ]);

        $response->assertStatus(422);
    }

    public function testCreateAppTokenWithInvalidPermissions(): void
    {
        $app = App::factory()->create();

        $response = $this->withHeaders($this->getAuthHeader())
            ->postJson('/admin/apps/' . $app->app_id . '/tokens', [
                'name' => 'Test Token',
                'permissions' => ['invalid.permission'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('permissions');
    }
}
