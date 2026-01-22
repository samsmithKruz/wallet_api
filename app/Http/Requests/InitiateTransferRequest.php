<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateTransferRequest extends FormRequest
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
            'sender_wallet_id' => [
                'required',
                'integer',
                'exists:wallets,id',
            ],
            'receiver_wallet_id' => [
                'required',
                'integer',
                'exists:wallets,id',
                'different:sender_wallet_id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:99999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'sender_wallet_id.required' => 'Sender wallet ID is required.',
            'sender_wallet_id.exists' => 'Sender wallet not found.',
            'receiver_wallet_id.required' => 'Receiver wallet ID is required.',
            'receiver_wallet_id.exists' => 'Receiver wallet not found.',
            'receiver_wallet_id.different' => 'Sender and receiver cannot be the same.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be at least 1.',
            'amount.max' => 'Amount is too large.',
            'amount.regex' => 'Amount must have up to 2 decimal places.',
            'description.max' => 'Description must not exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sender_wallet_id' => 'sender wallet',
            'receiver_wallet_id' => 'receiver wallet',
            'amount' => 'amount',
            'description' => 'description',
        ];
    }
}
