<?php

namespace App\Http\Requests\Api\V1\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
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
        $validEvents = [
            'payment.created',
            'payment.approved',
            'payment.declined',
            'payment.cancelled',
            'payment.refunded',
            'transaction.created',
            'transaction.completed',
            'transaction.failed',
            'wallet.balance_updated',
        ];

        return [
            'url' => ['nullable', 'url', 'max:500', 'regex:/^https:\/\/.+/'],
            'events' => ['nullable', 'array', 'min:1'],
            'events.*' => [
                'required',
                'string',
                'in:' . implode(',', $validEvents),
            ],
            'is_active' => ['nullable', 'boolean'],
            'retry_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:60'],
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
            'url.url' => 'A URL do webhook deve ser uma URL válida.',
            'url.max' => 'A URL do webhook não pode ter mais de 500 caracteres.',
            'url.regex' => 'A URL do webhook deve usar HTTPS.',
            'events.array' => 'Os eventos devem ser fornecidos em formato de array.',
            'events.min' => 'Pelo menos um evento deve ser especificado.',
            'events.*.required' => 'Cada evento é obrigatório.',
            'events.*.string' => 'Cada evento deve ser uma string válida.',
            'events.*.in' => 'Um ou mais eventos são inválidos. Eventos válidos: payment.created, payment.approved, payment.declined, payment.cancelled, payment.refunded, transaction.created, transaction.completed, transaction.failed, wallet.balance_updated',
            'is_active.boolean' => 'O status ativo deve ser verdadeiro ou falso.',
            'retry_count.integer' => 'O número de tentativas deve ser um número inteiro.',
            'retry_count.min' => 'O número de tentativas não pode ser negativo.',
            'retry_count.max' => 'O número de tentativas não pode exceder 10.',
            'timeout.integer' => 'O timeout deve ser um número inteiro.',
            'timeout.min' => 'O timeout deve ser no mínimo 5 segundos.',
            'timeout.max' => 'O timeout não pode exceder 60 segundos.',
        ];
    }
}
