<?php

namespace App\Http\Requests\Admin\Transactions;

use Illuminate\Foundation\Http\FormRequest;

class RefundTransactionRequest extends FormRequest
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
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
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
            'amount.numeric' => 'O valor do reembolso deve ser um número.',
            'amount.min' => 'O valor do reembolso deve ser no mínimo 0.01.',
            'amount.max' => 'O valor do reembolso não pode exceder 999999999.99.',
            'reason.required' => 'O motivo do reembolso é obrigatório.',
            'reason.string' => 'O motivo do reembolso deve ser um texto válido.',
            'reason.min' => 'O motivo do reembolso deve ter no mínimo 10 caracteres.',
            'reason.max' => 'O motivo do reembolso não pode ter mais de 500 caracteres.',
        ];
    }
}
