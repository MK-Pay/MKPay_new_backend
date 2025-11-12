<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Models\Tenant\Currency;
use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency' => [
                'required',
                'string',
                'size:3',
                function ($attribute, $value, $fail): void {
                    $currency = Currency::where('code', $value)->first();

                    if (! $currency) {
                        $fail('A moeda especificada não é suportada.');
                    }
                },
            ],
            'payment_method' => ['required', 'string', 'in:pix,credit_card,debit_card,boleto'],
            'description' => ['required', 'string', 'max:255'],

            // Customer data
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.cpf' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]{11}$/'],
            'customer.cnpj' => ['nullable', 'string', 'size:14', 'regex:/^[0-9]{14}$/'],
            'customer.phone' => ['nullable', 'string', 'max:20'],

            // Card data (required for credit/debit card)
            'card' => ['required_if:payment_method,credit_card,debit_card', 'nullable', 'array'],
            'card.number' => [
                'required_if:payment_method,credit_card,debit_card',
                'nullable',
                'string',
                'regex:/^[0-9]{13,19}$/',
            ],
            'card.holder_name' => ['required_if:payment_method,credit_card,debit_card', 'nullable', 'string', 'max:255'],
            'card.expiry_month' => [
                'required_if:payment_method,credit_card,debit_card',
                'nullable',
                'string',
                'size:2',
                'regex:/^(0[1-9]|1[0-2])$/',
            ],
            'card.expiry_year' => [
                'required_if:payment_method,credit_card,debit_card',
                'nullable',
                'string',
                'size:4',
                'regex:/^20[0-9]{2}$/',
                function ($attribute, $value, $fail): void {
                    if ($value && intval($value) < intval(date('Y'))) {
                        $fail('O ano de validade do cartão está expirado.');
                    }
                },
            ],
            'card.cvv' => [
                'required_if:payment_method,credit_card,debit_card',
                'nullable',
                'string',
                'regex:/^[0-9]{3,4}$/',
            ],

            // Other fields
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $paymentMethod = $this->input('payment_method');

            // Validate card expiry date
            if (in_array($paymentMethod, ['credit_card', 'debit_card']) && $this->has('card')) {
                $expiryMonth = $this->input('card.expiry_month');
                $expiryYear = $this->input('card.expiry_year');

                if ($expiryMonth && $expiryYear) {
                    $currentYear = intval(date('Y'));
                    $currentMonth = intval(date('m'));
                    $cardYear = intval($expiryYear);
                    $cardMonth = intval($expiryMonth);

                    if ($cardYear === $currentYear && $cardMonth < $currentMonth) {
                        $validator->errors()->add('card.expiry_month', 'O cartão está expirado.');
                    }
                }
            }

            // Validate customer has either CPF or CNPJ
            $cpf = $this->input('customer.cpf');
            $cnpj = $this->input('customer.cnpj');

            if (empty($cpf) && empty($cnpj)) {
                $validator->errors()->add('customer.cpf', 'O CPF ou CNPJ do cliente é obrigatório.');
            }

            // Validate installments only for credit card
            if ($paymentMethod !== 'credit_card' && $this->has('installments') && $this->input('installments') > 1) {
                $validator->errors()->add('installments', 'Parcelamento só é permitido para pagamentos com cartão de crédito.');
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
            'amount.required' => 'O valor do pagamento é obrigatório.',
            'amount.numeric' => 'O valor do pagamento deve ser um número.',
            'amount.min' => 'O valor do pagamento deve ser no mínimo 0.01.',
            'amount.max' => 'O valor do pagamento não pode exceder 999999999.99.',
            'currency.required' => 'A moeda é obrigatória.',
            'currency.size' => 'O código da moeda deve ter 3 caracteres.',
            'payment_method.required' => 'O método de pagamento é obrigatório.',
            'payment_method.in' => 'O método de pagamento deve ser: pix, credit_card, debit_card ou boleto.',
            'description.required' => 'A descrição é obrigatória.',
            'description.max' => 'A descrição não pode ter mais de 255 caracteres.',

            'customer.required' => 'Os dados do cliente são obrigatórios.',
            'customer.array' => 'Os dados do cliente devem ser fornecidos em formato de objeto.',
            'customer.name.required' => 'O nome do cliente é obrigatório.',
            'customer.name.max' => 'O nome do cliente não pode ter mais de 255 caracteres.',
            'customer.email.required' => 'O email do cliente é obrigatório.',
            'customer.email.email' => 'O email do cliente deve ser um endereço válido.',
            'customer.cpf.size' => 'O CPF deve ter 11 dígitos.',
            'customer.cpf.regex' => 'O CPF deve conter apenas números.',
            'customer.cnpj.size' => 'O CNPJ deve ter 14 dígitos.',
            'customer.cnpj.regex' => 'O CNPJ deve conter apenas números.',

            'card.required_if' => 'Os dados do cartão são obrigatórios para pagamentos com cartão.',
            'card.number.required_if' => 'O número do cartão é obrigatório.',
            'card.number.regex' => 'O número do cartão deve conter apenas números (13-19 dígitos).',
            'card.holder_name.required_if' => 'O nome do titular do cartão é obrigatório.',
            'card.expiry_month.required_if' => 'O mês de validade do cartão é obrigatório.',
            'card.expiry_month.regex' => 'O mês de validade deve ser entre 01 e 12.',
            'card.expiry_year.required_if' => 'O ano de validade do cartão é obrigatório.',
            'card.expiry_year.regex' => 'O ano de validade deve estar no formato AAAA (ex: 2025).',
            'card.cvv.required_if' => 'O CVV do cartão é obrigatório.',
            'card.cvv.regex' => 'O CVV deve conter 3 ou 4 dígitos.',

            'installments.integer' => 'O número de parcelas deve ser um número inteiro.',
            'installments.min' => 'O número de parcelas deve ser no mínimo 1.',
            'installments.max' => 'O número de parcelas não pode exceder 12.',
            'metadata.array' => 'Os metadados devem ser fornecidos em formato de array.',
        ];
    }
}
