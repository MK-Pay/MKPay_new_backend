<?php

namespace App\Http\Requests\Admin\Apps;

use Illuminate\Foundation\Http\FormRequest;

class CreateTokenRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [
                'string',
                'in:payments.read,payments.write,transactions.read,wallets.read,wallets.write,webhooks.read,webhooks.write,account.read,account.write',
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
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
            'name.required' => 'O nome do token é obrigatório.',
            'name.string' => 'O nome do token deve ser um texto válido.',
            'name.max' => 'O nome do token não pode ter mais de 255 caracteres.',
            'permissions.required' => 'Pelo menos uma permissão deve ser especificada.',
            'permissions.array' => 'As permissões devem ser fornecidas em formato de array.',
            'permissions.min' => 'Pelo menos uma permissão deve ser especificada.',
            'permissions.*.string' => 'Cada permissão deve ser uma string válida.',
            'permissions.*.in' => 'Uma ou mais permissões são inválidas. Permissões válidas: payments.read, payments.write, transactions.read, wallets.read, wallets.write, webhooks.read, webhooks.write, account.read, account.write',
            'expires_at.date' => 'A data de expiração deve ser uma data válida.',
            'expires_at.after' => 'A data de expiração deve ser uma data futura.',
        ];
    }
}
