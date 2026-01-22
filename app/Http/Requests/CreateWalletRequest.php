<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWalletRequest extends FormRequest
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
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('wallets', 'user_id'),
            ],
            'initial_balance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'User already has a wallet.',
            'initial_balance.min' => 'Initial balance cannot be negative.',
            'initial_balance.max' => 'Initial balance is too large.',
            'initial_balance.regex' => 'Initial balance must have up to 2 decimal places.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'initial_balance' => 'initial balance',
        ];
    }
}
