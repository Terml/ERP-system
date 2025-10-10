<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['manager', 'dispatcher']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => [
                'sometimes',
                'integer',
                'min:1',
                'max:1000'
            ],
            'deadline' => [
                'sometimes',
                'date',
                'after:today'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'quantity.min' => 'Количество должно быть больше 0',
            'quantity.max' => 'Количество не может превышать 1000',
            'deadline.after' => 'Срок выполнения должен быть в будущем',
        ];
    }
    public function attributes(): array
    {
        return [
            'quantity' => 'количество',
            'deadline' => 'срок выполнения',
        ];
    }
}