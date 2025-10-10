<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(roleName: 'otk');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:1000'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Причина отклонения обязательна',
            'rejection_reason.max' => 'Причина отклонения не может превышать 1000 символов',
        ];
    }
    public function attributes(): array
    {
        return [
            'rejection_reason' => 'причина отклонения',
        ];
    }
}