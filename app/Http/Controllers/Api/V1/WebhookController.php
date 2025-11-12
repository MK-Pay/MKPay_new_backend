<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Webhooks\StoreWebhookRequest;
use App\Http\Requests\Api\V1\Webhooks\UpdateWebhookRequest;
use App\Models\Tenant\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    /**
     * List webhooks for the authenticated account
     *
     * GET /api/v1/webhooks
     */
    public function index(Request $request): JsonResponse
    {
        $account = $request->get('account');
        $app = $request->get('app');

        $webhooks = Webhook::where('account_id', $account->id)
            ->where('app_id', $app->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $webhooks,
        ], 200);
    }

    /**
     * Create a new webhook
     *
     * POST /api/v1/webhooks
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $account = $request->get('account');
        $app = $request->get('app');

        try {
            $webhook = Webhook::create([
                'account_id' => $account->id,
                'app_id' => $app->id,
                'url' => $validated['url'],
                'events' => $validated['events'],
                'secret' => $validated['secret'] ?? null,
                'is_active' => true,
                'retry_count' => $validated['retry_count'] ?? 3,
                'timeout' => $validated['timeout'] ?? 30,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook created successfully.',
                'data' => $webhook,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create webhook.',
                'errors' => ['webhook' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Get webhook details
     *
     * GET /api/v1/webhooks/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $account = $request->get('account');
        $app = $request->get('app');

        $webhook = Webhook::where('id', $id)
            ->where('account_id', $account->id)
            ->where('app_id', $app->id)
            ->with('logs')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $webhook,
        ], 200);
    }

    /**
     * Update webhook
     *
     * PUT /api/v1/webhooks/{id}
     */
    public function update(UpdateWebhookRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $account = $request->get('account');
        $app = $request->get('app');

        try {
            $webhook = Webhook::where('id', $id)
                ->where('account_id', $account->id)
                ->where('app_id', $app->id)
                ->firstOrFail();

            $webhook->update(array_filter($validated, fn ($value) => $value !== null));

            return response()->json([
                'success' => true,
                'message' => 'Webhook updated successfully.',
                'data' => $webhook->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update webhook.',
                'errors' => ['webhook' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Delete webhook
     *
     * DELETE /api/v1/webhooks/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = $request->get('account');
        $app = $request->get('app');

        try {
            $webhook = Webhook::where('id', $id)
                ->where('account_id', $account->id)
                ->where('app_id', $app->id)
                ->firstOrFail();

            $webhook->delete();

            return response()->json([
                'success' => true,
                'message' => 'Webhook deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete webhook.',
                'errors' => ['webhook' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Test webhook by sending a test event
     *
     * POST /api/v1/webhooks/{id}/test
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $account = $request->get('account');
        $app = $request->get('app');

        $webhook = Webhook::where('id', $id)
            ->where('account_id', $account->id)
            ->where('app_id', $app->id)
            ->firstOrFail();

        if (! $webhook->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot test inactive webhook.',
                'errors' => ['webhook' => ['Webhook is not active.']],
            ], 422);
        }

        try {
            $testPayload = [
                'event' => 'webhook.test',
                'data' => [
                    'test' => true,
                    'timestamp' => now()->toIso8601String(),
                    'webhook_id' => $webhook->id,
                ],
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'X-MKPay-Event' => 'webhook.test',
            ];

            if ($webhook->secret) {
                $headers['X-MKPay-Signature'] = hash_hmac('sha256', json_encode($testPayload), $webhook->secret);
            }

            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, $testPayload);

            return response()->json([
                'success' => true,
                'message' => 'Test webhook sent successfully.',
                'data' => [
                    'status_code' => $response->status(),
                    'success' => $response->successful(),
                    'response_time' => $response->transferStats?->getTransferTime(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test webhook.',
                'errors' => ['webhook' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Get webhook logs
     *
     * GET /api/v1/webhooks/{id}/logs
     */
    public function logs(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $account = $request->get('account');
        $app = $request->get('app');

        $webhook = Webhook::where('id', $id)
            ->where('account_id', $account->id)
            ->where('app_id', $app->id)
            ->firstOrFail();

        $limit = $validated['limit'] ?? 50;
        $logs = $webhook->logs()->latest()->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }
}
