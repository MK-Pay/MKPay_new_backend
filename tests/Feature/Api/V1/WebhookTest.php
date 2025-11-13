<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class WebhookTest extends TenantTestCase
{
    use RefreshDatabase;

    protected App $testApp;
    protected Account $account;
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
            'permissions' => ['webhooks.create', 'webhooks.read', 'webhooks.write', 'webhooks.delete'],
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

    public function testListWebhooksWithValidCredentials(): void
    {
        Webhook::factory(3)->create(['app_id' => $this->testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'url', 'events', 'is_active']],
        ]);
    }

    public function testListWebhooksWithoutCredentials(): void
    {
        $response = $this->getJson('/api/v1/webhooks');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Authentication credentials are required.');
    }

    public function testListWebhooksWithInvalidAppSecret(): void
    {
        $response = $this->withHeaders([
            'X-App-Id' => $this->testApp->app_id,
            'X-App-Secret' => 'invalid-secret',
        ])->getJson('/api/v1/webhooks');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid application credentials.');
    }

    public function testListWebhooksWithInactiveApp(): void
    {
        $this->testApp->update(['is_active' => false]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Application is inactive.');
    }

    public function testListWebhooksOnlyReturnsAppWebhooks(): void
    {
        $otherApp = App::factory()->create();
        Webhook::factory(3)->create(['app_id' => $this->testApp->id]);
        Webhook::factory(2)->create(['app_id' => $otherApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    public function testCreateWebhookWithValidData(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/webhook',
                'events' => ['payment.created', 'payment.approved'],
                'description' => 'Test webhook',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'url', 'events'],
        ]);
    }

    public function testCreateWebhookRequiresURL(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'events' => ['payment.created'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('url');
    }

    public function testCreateWebhookRequiresValidURL(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'url' => 'not-a-valid-url',
                'events' => ['payment.created'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('url');
    }

    public function testCreateWebhookRequiresEvents(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/webhook',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('events');
    }

    public function testCreateWebhookRequiresEventsArray(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/webhook',
                'events' => 'single_event',
            ]);

        $response->assertStatus(422);
    }

    public function testCreateWebhookWithMultipleEvents(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/webhook',
                'events' => ['payment.created', 'payment.approved', 'refund.created'],
            ]);

        $response->assertStatus(201);
        $this->assertCount(3, $response->json('data.events'));
    }

    public function testGetWebhookDetails(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks/' . $webhook->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $webhook->id);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'url', 'events', 'is_active'],
        ]);
    }

    public function testGetNonExistentWebhook(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks/99999');

        $response->assertStatus(404);
    }

    public function testCannotGetOtherAppWebhook(): void
    {
        $otherApp = App::factory()->create();
        $webhook = Webhook::factory()->create(['app_id' => $otherApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks/' . $webhook->id);

        $response->assertStatus(404);
    }

    public function testUpdateWebhookWithValidData(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/webhooks/' . $webhook->id, [
                'url' => 'https://newurl.com/webhook',
                'events' => ['payment.created'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.url', 'https://newurl.com/webhook');
    }

    public function testUpdateWebhookURL(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);
        $newURL = 'https://updated.com/webhook';

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/webhooks/' . $webhook->id, [
                'url' => $newURL,
            ]);

        $response->assertStatus(200);
        $this->assertEquals($newURL, $response->json('data.url'));
    }

    public function testUpdateWebhookEvents(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);
        $newEvents = ['refund.created', 'refund.approved'];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/webhooks/' . $webhook->id, [
                'events' => $newEvents,
            ]);

        $response->assertStatus(200);
    }

    public function testDeleteWebhook(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);
        $webhookId = $webhook->id;

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson('/api/v1/webhooks/' . $webhookId);

        $response->assertStatus(200);

        // Verify webhook is deleted
        $this->assertNull(Webhook::find($webhookId));
    }

    public function testCannotDeleteNonExistentWebhook(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson('/api/v1/webhooks/99999');

        $response->assertStatus(404);
    }

    public function testCannotDeleteOtherAppWebhook(): void
    {
        $otherApp = App::factory()->create();
        $webhook = Webhook::factory()->create(['app_id' => $otherApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson('/api/v1/webhooks/' . $webhook->id);

        $response->assertStatus(404);
    }

    public function testTestWebhookWithValidPayload(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks/' . $webhook->id . '/test');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testTestNonExistentWebhook(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks/99999/test');

        $response->assertStatus(404);
    }

    public function testGetWebhookLogs(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks/' . $webhook->id . '/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'event', 'status', 'created_at']],
        ]);
    }

    public function testGetWebhookLogsWithPagination(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks/' . $webhook->id . '/logs?per_page=10');

        $response->assertStatus(200);
    }

    public function testGetLogsForNonExistentWebhook(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/webhooks/99999/logs');

        $response->assertStatus(404);
    }

    public function testWebhookIsActiveByDefault(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/webhook',
                'events' => ['payment.created'],
            ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('data.is_active'));
    }

    public function testUpdateWebhookCanDisable(): void
    {
        $webhook = Webhook::factory()->create(['app_id' => $this->testApp->id, 'is_active' => true]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/webhooks/' . $webhook->id, [
                'is_active' => false,
            ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_active'));
    }
}
