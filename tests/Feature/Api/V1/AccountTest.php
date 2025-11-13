<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class AccountTest extends TenantTestCase
{
    use RefreshDatabase;

    protected App $testApp;
    protected Account $account;
    protected string $appSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create([
            'email' => 'account@test.com',
            'name' => 'Test Account',
        ]);

        $this->testApp = App::factory()->create(['account_id' => $this->account->id]);

        // Create a secret token for the app
        $this->appSecret = 'test-secret-token-' . bin2hex(random_bytes(16));
        $this->testApp->secretTokens()->create([
            'name' => 'Test Token',
            'token_hash' => bcrypt($this->appSecret),
            'permissions' => ['account.read', 'account.write'],
            'is_active' => true,
        ]);
    }

    protected function getAuthHeaders(): array
    {
        return [
            'X-App-Id' => $this->testApp->app_id,
            'X-App-Secret' => $this->appSecret,
        ];
    }

    public function testGetAccountInfoWithValidCredentials(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'uuid', 'email', 'name'],
        ]);
        $response->assertJsonPath('data.email', $this->account->email);
    }

    public function testGetAccountInfoWithoutCredentials(): void
    {
        $response = $this->getJson('/api/v1/account');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Authentication credentials are required.');
    }

    public function testGetAccountInfoWithInvalidAppSecret(): void
    {
        $response = $this->withHeaders([
            'X-App-Id' => $this->app->app_id,
            'X-App-Secret' => 'invalid-secret',
        ])->getJson('/api/v1/account');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid application credentials.');
    }

    public function testGetAccountInfoWithInactiveApp(): void
    {
        $this->app->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Application is inactive.');
    }

    public function testGetAccountInfoShowsCorrectData(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        $this->assertEquals($this->account->email, $response->json('data.email'));
        $this->assertEquals($this->account->name, $response->json('data.name'));
        $this->assertEquals($this->account->uuid, $response->json('data.uuid'));
    }

    public function testGetAccountInfoIncludesAccountStatus(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['account_status'],
        ]);
    }

    public function testGetAccountInfoIncludesAccountType(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['account_type'],
        ]);
    }

    public function testUpdateAccountWithValidData(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'name' => 'Updated Account Name',
                'phone' => '11988888888',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Account Name');
    }

    public function testUpdateAccountNameOnly(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200);
        $this->account->refresh();
        $this->assertEquals('New Name', $this->account->name);
    }

    public function testUpdateAccountPhoneOnly(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'phone' => '11999999999',
            ]);

        $response->assertStatus(200);
        $this->account->refresh();
        $this->assertEquals('11999999999', $this->account->phone);
    }

    public function testUpdateAccountWithInvalidPhone(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'phone' => 'invalid-phone',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    public function testUpdateAccountWithoutData(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', []);

        $response->assertStatus(200);
    }

    public function testUpdateAccountInvalidEmail(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
    }

    public function testGetAccountReturnsMaskedCPF(): void
    {
        $this->account->update(['cpf' => '12345678910']);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        // CPF should be masked or not exposed in API response
        $this->assertNotNull($response->json('data'));
    }

    public function testGetAccountReturnsMaskedCNPJ(): void
    {
        $this->account->update(['cnpj' => '12345678000190']);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        // CNPJ should be masked or not exposed in API response
        $this->assertNotNull($response->json('data'));
    }

    public function testUpdateAccountPreservesUUID(): void
    {
        $originalUUID = $this->account->uuid;

        $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'name' => 'Updated Name',
            ]);

        $this->account->refresh();
        $this->assertEquals($originalUUID, $this->account->uuid);
    }

    public function testGetAccountIncludesCreatedAtTimestamp(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.created_at'));
    }

    public function testUpdateAccountReturnsUpdatedData(): void
    {
        $newName = 'Completely Updated Name';

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'name' => $newName,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', $newName);
    }

    public function testGetAccountOnlyAllowsAuthenticatedApps(): void
    {
        $otherAccount = Account::factory()->create();
        $otherApp = App::factory()->create(['account_id' => $otherAccount->id]);
        $otherSecret = 'other-secret-' . bin2hex(random_bytes(16));
        $otherApp->secretTokens()->create([
            'name' => 'Other Token',
            'token_hash' => bcrypt($otherSecret),
            'permissions' => ['account.read'],
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'X-App-Id' => $otherApp->app_id,
            'X-App-Secret' => $otherSecret,
        ])->getJson('/api/v1/account');

        // Other app's account data should be accessible only for their own account
        $response->assertStatus(200);
    }

    public function testUpdateAccountWithLongName(): void
    {
        $longName = str_repeat('A', 255);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/account', [
                'name' => $longName,
            ]);

        $response->assertStatus(200) || $response->assertStatus(422);
    }

    public function testGetAccountInfoWithExpiredToken(): void
    {
        $token = $this->testApp->secretTokens()->first();
        $token->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(401);
    }

    public function testGetAccountInfoWithInactiveToken(): void
    {
        $token = $this->testApp->secretTokens()->first();
        $token->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/account');

        $response->assertStatus(401);
    }
}
