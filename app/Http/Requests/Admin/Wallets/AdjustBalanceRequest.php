<?php

namespace App\Http\Requests\Admin\Wallets;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'metadata' => ['nullable', 'array'],
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
            'amount.required' => 'O valor do ajuste é obrigatório.',
            'amount.numeric' => 'O valor do ajuste deve ser um número.',
            'amount.not_in' => 'O valor do ajuste não pode ser zero.',
            'reason.required' => 'O motivo do ajuste é obrigatório.',
            'reason.string' => 'O motivo do ajuste deve ser um texto válido.',
            'reason.min' => 'O motivo do ajuste deve ter no mínimo 10 caracteres.',
            'reason.max' => 'O motivo do ajuste não pode ter mais de 500 caracteres.',
            'metadata.array' => 'Os metadados devem ser fornecidos em formato de array.',
        ];
    }
}
