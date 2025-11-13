<?php

namespace App\Http\Requests\Api\V1\Apps;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'settings' => ['nullable', 'array'],
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
            'name.required' => 'O nome do aplicativo é obrigatório.',
            'name.string' => 'O nome do aplicativo deve ser um texto válido.',
            'name.max' => 'O nome do aplicativo não pode ter mais de 255 caracteres.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'description.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            'settings.array' => 'As configurações devem ser fornecidas em formato de array.',
        ];
    }
}
