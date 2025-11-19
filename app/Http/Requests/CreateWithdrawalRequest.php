<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Application\Payments\Dto\CreateWithdrawalData;

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
            'amount' => 'required|numeric|min:0.01',
            'bank_code' => 'required|string',
            'agency' => 'required|string',
            'account' => 'required|string',
            'account_type' => 'nullable|string|in:checking,savings',
            'holder_name' => 'required|string',
            'holder_document' => 'required|string',
        ];
    }

    public function toData(): CreateWithdrawalData
    {
        return CreateWithdrawalData::fromArray($this->validated());
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
