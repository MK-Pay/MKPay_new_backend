<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Account;
use App\Models\Tenant\AccountCategory;
use App\Models\Tenant\AccountStatus;
use App\Models\Tenant\AccountType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Account::query()
            ->with(['accountType', 'accountCategory', 'accountStatus']);

        // Filter by account type
        if ($request->has('type')) {
            $query->whereHas('accountType', function ($q) use ($request): void {
                $q->where('code', $request->type);
            });
        }

        // Filter by account status
        if ($request->has('status')) {
            $query->whereHas('accountStatus', function ($q) use ($request): void {
                $q->where('code', $request->status);
            });
        }

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('accountCategory', function ($q) use ($request): void {
                $q->where('code', $request->category);
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $accounts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $accounts->items(),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ], 200);
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type' => ['required', 'string', Rule::in(['PF', 'PJ'])],
            'account_category' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:accounts,email'],
            'cpf' => ['nullable', 'string', 'size:11', 'unique:accounts,cpf'],
            'cnpj' => ['nullable', 'string', 'size:14', 'unique:accounts,cnpj'],
            'phone' => ['required', 'string', 'max:20'],
            'usage_types' => ['required', 'array'],
            'usage_types.*' => ['string'],
            'hourly_transaction_limit' => ['nullable', 'numeric', 'min:0'],
            'daily_transaction_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Validate CPF for PF or CNPJ for PJ
        if ($validated['account_type'] === 'PF' && empty($validated['cpf'])) {
            return response()->json([
                'success' => false,
                'message' => 'CPF is required for individual accounts.',
                'errors' => ['cpf' => ['CPF is required for account type PF.']],
            ], 422);
        }

        if ($validated['account_type'] === 'PJ' && empty($validated['cnpj'])) {
            return response()->json([
                'success' => false,
                'message' => 'CNPJ is required for business accounts.',
                'errors' => ['cnpj' => ['CNPJ is required for account type PJ.']],
            ], 422);
        }

        // Get account type
        $accountType = AccountType::where('code', $validated['account_type'])->first();

        if (! $accountType) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid account type.',
                'errors' => ['account_type' => ['The selected account type is invalid.']],
            ], 422);
        }

        // Get account category (optional)
        $accountCategory = null;

        if (! empty($validated['account_category'])) {
            $accountCategory = AccountCategory::where('code', $validated['account_category'])->first();
        }

        // Get default status (pending)
        $defaultStatus = AccountStatus::where('code', 'pending')->first();

        // Create account
        $account = Account::create([
            'uuid' => Str::uuid(),
            'account_type_id' => $accountType->id,
            'account_category_id' => $accountCategory?->id,
            'account_status_id' => $defaultStatus->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'] ?? null,
            'cnpj' => $validated['cnpj'] ?? null,
            'phone' => $validated['phone'],
            'usage_types' => $validated['usage_types'],
            'hourly_transaction_limit' => $validated['hourly_transaction_limit'] ?? null,
            'daily_transaction_limit' => $validated['daily_transaction_limit'] ?? null,
        ]);

        $account->load(['accountType', 'accountCategory', 'accountStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => $account,
        ], 201);
    }

    /**
     * Display the specified account.
     */
    public function show(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)
            ->with(['accountType', 'accountCategory', 'accountStatus', 'apps', 'wallets'])
            ->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $account,
        ], 200);
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('accounts', 'email')->ignore($account->id)],
            'phone' => ['sometimes', 'string', 'max:20'],
            'usage_types' => ['sometimes', 'array'],
            'usage_types.*' => ['string'],
            'hourly_transaction_limit' => ['nullable', 'numeric', 'min:0'],
            'daily_transaction_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $account->update($validated);
        $account->load(['accountType', 'accountCategory', 'accountStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully.',
            'data' => $account,
        ], 200);
    }

    /**
     * Remove the specified account (soft delete).
     */
    public function destroy(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ], 200);
    }

    /**
     * Suspend an account.
     */
    public function suspend(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $suspendedStatus = AccountStatus::where('code', 'suspended')->first();

        $account->update(['account_status_id' => $suspendedStatus->id]);
        $account->load(['accountType', 'accountCategory', 'accountStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Account suspended successfully.',
            'data' => $account,
        ], 200);
    }

    /**
     * Activate an account.
     */
    public function activate(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $activeStatus = AccountStatus::where('code', 'validated')->first();

        $account->update(['account_status_id' => $activeStatus->id]);
        $account->load(['accountType', 'accountCategory', 'accountStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Account activated successfully.',
            'data' => $account,
        ], 200);
    }

    /**
     * Verify an account.
     */
    public function verify(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $verifiedStatus = AccountStatus::where('code', 'verified')->first();

        $account->update([
            'account_status_id' => $verifiedStatus->id,
            'verified_at' => now(),
        ]);
        $account->load(['accountType', 'accountCategory', 'accountStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Account verified successfully.',
            'data' => $account,
        ], 200);
    }

    /**
     * Get activity log for an account.
     */
    public function activity(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $activities = $account->activities()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ], 200);
    }
}
