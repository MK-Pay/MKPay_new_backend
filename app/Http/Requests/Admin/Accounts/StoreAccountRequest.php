<?php

namespace App\Http\Requests\Admin\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
            'account_type' => ['required', 'string', Rule::in(['PF', 'PJ'])],
            'account_category' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:accounts,email'],
            'cpf' => [
                'nullable',
                'string',
                'size:11',
                'unique:accounts,cpf',
                'regex:/^[0-9]{11}$/',
                function ($attribute, $value, $fail): void {
                    if ($value && ! $this->isValidCPF($value)) {
                        $fail('O CPF informado é inválido.');
                    }
                },
            ],
            'cnpj' => [
                'nullable',
                'string',
                'size:14',
                'unique:accounts,cnpj',
                'regex:/^[0-9]{14}$/',
                function ($attribute, $value, $fail): void {
                    if ($value && ! $this->isValidCNPJ($value)) {
                        $fail('O CNPJ informado é inválido.');
                    }
                },
            ],
            'phone' => ['required', 'string', 'max:20'],
            'usage_types' => ['required', 'array', 'min:1'],
            'usage_types.*' => ['string', 'max:50'],
            'hourly_transaction_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'daily_transaction_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $accountType = $this->input('account_type');
            $cpf = $this->input('cpf');
            $cnpj = $this->input('cnpj');

            // Validate CPF is required for PF
            if ($accountType === 'PF' && empty($cpf)) {
                $validator->errors()->add('cpf', 'O CPF é obrigatório para contas do tipo Pessoa Física.');
            }

            // Validate CNPJ is required for PJ
            if ($accountType === 'PJ' && empty($cnpj)) {
                $validator->errors()->add('cnpj', 'O CNPJ é obrigatório para contas do tipo Pessoa Jurídica.');
            }

            // Validate that PF accounts should not have CNPJ
            if ($accountType === 'PF' && ! empty($cnpj)) {
                $validator->errors()->add('cnpj', 'Contas de Pessoa Física não podem ter CNPJ.');
            }

            // Validate that PJ accounts should not have CPF
            if ($accountType === 'PJ' && ! empty($cpf)) {
                $validator->errors()->add('cpf', 'Contas de Pessoa Jurídica não podem ter CPF.');
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
            'account_type.required' => 'O tipo de conta é obrigatório.',
            'account_type.in' => 'O tipo de conta deve ser PF (Pessoa Física) ou PJ (Pessoa Jurídica).',
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço válido.',
            'email.unique' => 'Este email já está cadastrado.',
            'cpf.size' => 'O CPF deve ter exatamente 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cpf.regex' => 'O CPF deve conter apenas números.',
            'cnpj.size' => 'O CNPJ deve ter exatamente 14 dígitos.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'cnpj.regex' => 'O CNPJ deve conter apenas números.',
            'phone.required' => 'O telefone é obrigatório.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'usage_types.required' => 'Pelo menos um tipo de uso deve ser informado.',
            'usage_types.array' => 'Os tipos de uso devem ser informados em um array.',
            'usage_types.min' => 'Pelo menos um tipo de uso deve ser informado.',
            'hourly_transaction_limit.numeric' => 'O limite horário de transações deve ser um número.',
            'hourly_transaction_limit.min' => 'O limite horário de transações não pode ser negativo.',
            'daily_transaction_limit.numeric' => 'O limite diário de transações deve ser um número.',
            'daily_transaction_limit.min' => 'O limite diário de transações não pode ser negativo.',
        ];
    }

    /**
     * Validate CPF using official algorithm.
     */
    protected function isValidCPF(string $cpf): bool
    {
        // Remove non-numeric characters
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Check if has 11 digits
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Check for known invalid CPFs
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($cpf[9]) !== $digit1) {
            return false;
        }

        // Validate second check digit
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return intval($cpf[10]) === $digit2;
    }

    /**
     * Validate CNPJ using official algorithm.
     */
    protected function isValidCNPJ(string $cnpj): bool
    {
        // Remove non-numeric characters
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Check if has 14 digits
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Check for known invalid CNPJs
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($cnpj[12]) !== $digit1) {
            return false;
        }

        // Validate second check digit
        $sum = 0;
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpj[$i]) * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return intval($cnpj[13]) === $digit2;
    }
}
