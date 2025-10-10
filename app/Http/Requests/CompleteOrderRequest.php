<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('otk');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'completion_date' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ]
        ];
    }
    public function messages(): array
    {
        return [
            'completion_date.before_or_equal' => 'Дата завершения не может быть в будущем'
        ];
    }
    public function attributes(): array
    {
        return [
            'completion_date' => 'дата завершения'
        ];
    }
}