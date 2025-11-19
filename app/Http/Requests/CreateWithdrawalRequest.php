<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawalRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_code' => 'required|string',
            'agency' => 'required|string',
            'account' => 'required|string',
            'account_type' => 'nullable|string|in:checking,savings',
            'holder_name' => 'required|string',
            'holder_document' => 'required|string',
        ];
    }
}

