<?php

namespace App\Http\Requests\Admin\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
        // Get the account ID from route parameter
        $accountId = $this->route('uuid');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('accounts', 'email')->where(fn ($query) => $query->where('uuid', '!=', $accountId)),
            ],
            'phone' => ['sometimes', 'string', 'max:20'],
            'usage_types' => ['sometimes', 'array', 'min:1'],
            'usage_types.*' => ['string', 'max:50'],
            'hourly_transaction_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'daily_transaction_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
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
            'name.string' => 'O nome deve ser um texto válido.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'O email deve ser um endereço válido.',
            'email.unique' => 'Este email já está cadastrado.',
            'phone.string' => 'O telefone deve ser um texto válido.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'usage_types.array' => 'Os tipos de uso devem ser informados em um array.',
            'usage_types.min' => 'Pelo menos um tipo de uso deve ser informado.',
            'hourly_transaction_limit.numeric' => 'O limite horário de transações deve ser um número.',
            'hourly_transaction_limit.min' => 'O limite horário de transações não pode ser negativo.',
            'daily_transaction_limit.numeric' => 'O limite diário de transações deve ser um número.',
            'daily_transaction_limit.min' => 'O limite diário de transações não pode ser negativo.',
        ];
    }
}
