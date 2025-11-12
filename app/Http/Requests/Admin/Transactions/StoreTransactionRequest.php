<?php

namespace App\Http\Requests\Admin\Transactions;

use App\Models\Tenant\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                'in:payment_in,payment_out,deposit,withdrawal,transfer,hold,release',
            ],
            'origin_wallet_uuid' => [
                'nullable',
                'string',
                'uuid',
                function ($attribute, $value, $fail): void {
                    if ($value) {
                        $wallet = Wallet::where('uuid', $value)->first();

                        if (! $wallet) {
                            $fail('A carteira de origem especificada não existe.');
                        }
                    }
                },
            ],
            'destination_wallet_uuid' => [
                'nullable',
                'string',
                'uuid',
                function ($attribute, $value, $fail): void {
                    if ($value) {
                        $wallet = Wallet::where('uuid', $value)->first();

                        if (! $wallet) {
                            $fail('A carteira de destino especificada não existe.');
                        }
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:255'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('type');
            $originWalletUuid = $this->input('origin_wallet_uuid');
            $destinationWalletUuid = $this->input('destination_wallet_uuid');

            // Transfer requires both wallets
            if ($type === 'transfer') {
                if (empty($originWalletUuid)) {
                    $validator->errors()->add('origin_wallet_uuid', 'A carteira de origem é obrigatória para transferências.');
                }

                if (empty($destinationWalletUuid)) {
                    $validator->errors()->add('destination_wallet_uuid', 'A carteira de destino é obrigatória para transferências.');
                }
            }

            // Deposit requires destination wallet
            if (in_array($type, ['deposit', 'payment_in']) && empty($destinationWalletUuid)) {
                $validator->errors()->add('destination_wallet_uuid', 'A carteira de destino é obrigatória para depósitos e pagamentos de entrada.');
            }

            // Withdrawal requires origin wallet
            if (in_array($type, ['withdrawal', 'payment_out', 'hold', 'release']) && empty($originWalletUuid)) {
                $validator->errors()->add('origin_wallet_uuid', 'A carteira de origem é obrigatória para saques, pagamentos de saída, reservas e liberações.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de transação é obrigatório.',
            'type.string' => 'O tipo de transação deve ser uma string válida.',
            'type.in' => 'O tipo de transação deve ser: payment_in, payment_out, deposit, withdrawal, transfer, hold ou release.',
            'origin_wallet_uuid.string' => 'O UUID da carteira de origem deve ser uma string válida.',
            'origin_wallet_uuid.uuid' => 'O UUID da carteira de origem deve ser um UUID válido.',
            'destination_wallet_uuid.string' => 'O UUID da carteira de destino deve ser uma string válida.',
            'destination_wallet_uuid.uuid' => 'O UUID da carteira de destino deve ser um UUID válido.',
            'amount.required' => 'O valor da transação é obrigatório.',
            'amount.numeric' => 'O valor da transação deve ser um número.',
            'amount.min' => 'O valor da transação deve ser no mínimo 0.01.',
            'amount.max' => 'O valor da transação não pode exceder 999999999.99.',
            'fee.numeric' => 'A taxa deve ser um número.',
            'fee.min' => 'A taxa não pode ser negativa.',
            'fee.max' => 'A taxa não pode exceder 999999999.99.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'description.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            'reference.string' => 'A referência deve ser um texto válido.',
            'reference.max' => 'A referência não pode ter mais de 255 caracteres.',
            'external_reference.string' => 'A referência externa deve ser um texto válido.',
            'external_reference.max' => 'A referência externa não pode ter mais de 255 caracteres.',
            'metadata.array' => 'Os metadados devem ser fornecidos em formato de array.',
        ];
    }
}
