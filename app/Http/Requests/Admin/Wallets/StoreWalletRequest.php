<?php

namespace App\Http\Requests\Admin\Wallets;

use App\Models\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_uuid' => [
                'required',
                'string',
                'uuid',
                function ($attribute, $value, $fail): void {
                    $account = Account::where('uuid', $value)->first();

                    if (! $account) {
                        $fail('A conta especificada não existe.');
                    }
                },
            ],
            'currency_code' => [
                'required',
                'string',
                'size:3',
                function ($attribute, $value, $fail): void {
                    $currency = Currency::where('code', $value)->first();

                    if (! $currency) {
                        $fail('A moeda especificada não existe.');
                    }
                },
            ],
            'app_id' => [
                'nullable',
                'string',
                'uuid',
                function ($attribute, $value, $fail): void {
                    if ($value) {
                        $app = App::where('app_id', $value)->first();

                        if (! $app) {
                            $fail('O aplicativo especificado não existe.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_uuid.required' => 'O UUID da conta é obrigatório.',
            'account_uuid.string' => 'O UUID da conta deve ser uma string válida.',
            'account_uuid.uuid' => 'O UUID da conta deve ser um UUID válido.',
            'currency_code.required' => 'O código da moeda é obrigatório.',
            'currency_code.string' => 'O código da moeda deve ser uma string válida.',
            'currency_code.size' => 'O código da moeda deve ter exatamente 3 caracteres.',
            'app_id.string' => 'O ID do aplicativo deve ser uma string válida.',
            'app_id.uuid' => 'O ID do aplicativo deve ser um UUID válido.',
        ];
    }
}
