<?php

namespace App\Http\Requests\Api\V1\Account;

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
        $account = $this->get('account');

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('accounts', 'email')->ignore($account?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'zip_code' => ['nullable', 'string', 'max:9', 'regex:/^[0-9]{5}-?[0-9]{3}$/'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
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
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este email já está cadastrado.',
            'phone.string' => 'O telefone deve ser um texto válido.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'address.string' => 'O endereço deve ser um texto válido.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'city.string' => 'A cidade deve ser um texto válido.',
            'city.max' => 'A cidade não pode ter mais de 100 caracteres.',
            'state.string' => 'O estado deve ser um texto válido.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres (ex: SP, RJ).',
            'state.regex' => 'O estado deve conter apenas letras maiúsculas (ex: SP, RJ).',
            'zip_code.string' => 'O CEP deve ser um texto válido.',
            'zip_code.max' => 'O CEP não pode ter mais de 9 caracteres.',
            'zip_code.regex' => 'O CEP deve estar no formato 12345-678 ou 12345678.',
            'country.string' => 'O país deve ser um texto válido.',
            'country.size' => 'O código do país deve ter exatamente 2 caracteres (ex: BR, US).',
            'country.regex' => 'O código do país deve conter apenas letras maiúsculas (ex: BR, US).',
        ];
    }
}
