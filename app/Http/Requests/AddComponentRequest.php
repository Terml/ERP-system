<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddComponentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('master');
    }
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id'
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10000'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'product_id.required' => 'ID продукта обязателен для заполнения',
            'product_id.exists' => 'Выбранный продукт не существует',
            'quantity.required' => 'Количество обязательно для заполнения',
            'quantity.min' => 'Количество должно быть больше 0',
            'quantity.max' => 'Количество не может превышать 10000',
        ];
    }
    public function attributes(): array
    {
        return [
            'product_id' => 'продукт',
            'quantity' => 'количество',
        ];
    }
}
