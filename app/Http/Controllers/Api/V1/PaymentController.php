<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\CreatePaymentRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {
    }

    /**
     * List payments for the authenticated account
     *
     * GET /api/v1/payments
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'in:pix,credit_card,debit_card,boleto'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $account = $request->get('account');

        $query = Transaction::query()
            ->whereIn('type', ['payment_in', 'payment_out'])
            ->where(function ($q) use ($account): void {
                $q->whereHas('originWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                })->orWhereHas('destinationWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                });
            })
            ->with(['transactionStatus', 'originWallet', 'destinationWallet']);

        if (isset($validated['status'])) {
            $query->whereHas('transactionStatus', function ($q) use ($validated): void {
                $q->where('code', $validated['status']);
            });
        }

        if (isset($validated['payment_method'])) {
            $query->where('metadata->payment_method', $validated['payment_method']);
        }

        if (isset($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $payments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ], 200);
    }

    /**
     * Create a new payment
     *
     * POST /api/v1/payments
     */
    public function store(CreatePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $account = $request->get('account');
        $app = $request->get('app');

        try {
            $wallet = Wallet::where('account_id', $account->id)
                ->where('currency', $validated['currency'])
                ->firstOrFail();

            $transactionData = [
                'type' => 'payment_in',
                'destination_wallet_id' => $wallet->id,
                'app_id' => $app->id,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'description' => $validated['description'],
                'metadata' => array_merge($validated['metadata'] ?? [], [
                    'payment_method' => $validated['payment_method'],
                    'customer' => $validated['customer'],
                    'card' => isset($validated['card']) ? [
                        'last_four' => substr($validated['card']['number'], -4),
                        'brand' => $this->detectCardBrand($validated['card']['number']),
                        'holder_name' => $validated['card']['holder_name'],
                    ] : null,
                    'installments' => $validated['installments'] ?? 1,
                ]),
            ];

            $transaction = $this->transactionService->create($transactionData);

            if ($validated['payment_method'] === 'pix') {
                $transaction->update([
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'pix_qr_code' => 'PIX_QR_CODE_' . $transaction->uuid,
                        'pix_qr_code_text' => 'br.gov.bcb.pix://mock-qrcode-' . $transaction->uuid,
                        'pix_expiration' => now()->addMinutes(30)->toIso8601String(),
                    ]),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully.',
                'data' => $transaction->load(['transactionStatus', 'destinationWallet']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment.',
                'errors' => ['payment' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Get payment details
     *
     * GET /api/v1/payments/{uuid}
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $account = $request->get('account');

        $payment = Transaction::where('uuid', $uuid)
            ->whereIn('type', ['payment_in', 'payment_out'])
            ->where(function ($q) use ($account): void {
                $q->whereHas('originWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                })->orWhereHas('destinationWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                });
            })
            ->with(['transactionStatus', 'originWallet', 'destinationWallet', 'app'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ], 200);
    }

    /**
     * Cancel a payment
     *
     * POST /api/v1/payments/{uuid}/cancel
     */
    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $account = $request->get('account');

        try {
            $payment = Transaction::where('uuid', $uuid)
                ->whereIn('type', ['payment_in', 'payment_out'])
                ->where(function ($q) use ($account): void {
                    $q->whereHas('originWallet', function ($wq) use ($account): void {
                        $wq->where('account_id', $account->id);
                    })->orWhereHas('destinationWallet', function ($wq) use ($account): void {
                        $wq->where('account_id', $account->id);
                    });
                })
                ->firstOrFail();

            if ($payment->is_final) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a finalized payment.',
                    'errors' => ['payment' => ['Payment is already in a final state.']],
                ], 422);
            }

            $cancelledPayment = $this->transactionService->cancel($payment, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully.',
                'data' => $cancelledPayment->fresh(['transactionStatus']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment.',
                'errors' => ['payment' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Refund a payment
     *
     * POST /api/v1/payments/{uuid}/refund
     */
    public function refund(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ]);

        $account = $request->get('account');

        try {
            $payment = Transaction::where('uuid', $uuid)
                ->whereIn('type', ['payment_in', 'payment_out'])
                ->where(function ($q) use ($account): void {
                    $q->whereHas('originWallet', function ($wq) use ($account): void {
                        $wq->where('account_id', $account->id);
                    })->orWhereHas('destinationWallet', function ($wq) use ($account): void {
                        $wq->where('account_id', $account->id);
                    });
                })
                ->firstOrFail();

            $refundTransaction = $this->transactionService->refund(
                $payment,
                $validated['amount'] ?? null,
                $validated['reason']
            );

            if (isset($validated['metadata'])) {
                $refundTransaction->update([
                    'metadata' => array_merge($refundTransaction->metadata ?? [], $validated['metadata']),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully.',
                'data' => [
                    'original_payment' => $payment->fresh(['transactionStatus']),
                    'refund_transaction' => $refundTransaction->fresh(['transactionStatus']),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refund payment.',
                'errors' => ['refund' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Detect card brand from number
     */
    protected function detectCardBrand(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        $brands = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'elo' => '/^(4011|4312|4389|4514|5041|5066|5067|509|636368|636297)/',
            'hipercard' => '/^(38|60)/',
        ];

        foreach ($brands as $brand => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $brand;
            }
        }

        return 'unknown';
    }
}
