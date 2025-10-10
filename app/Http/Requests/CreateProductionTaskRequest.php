<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductionTaskRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('dispatcher');
    }
    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id'
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'order_id.required' => 'ID заказа обязателен для заполнения',
            'order_id.exists' => 'Выбранный заказ не существует',
            'quantity.required' => 'Количество обязательно для заполнения',
            'quantity.min' => 'Количество должно быть больше 0',
            'quantity.max' => 'Количество не может превышать 1000',
            'user_id.exists' => 'Выбранный пользователь не существует',
        ];
    }
    public function attributes(): array
    {
        return [
            'order_id' => 'заказ',
            'quantity' => 'количество',
            'user_id' => 'пользователь',
        ];
    }
}
