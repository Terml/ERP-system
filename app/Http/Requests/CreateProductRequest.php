<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('manager');
    }
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:products,name'
            ],
            'type' => [
                'required',
                'string',
                'in:product,material'
            ],
            'unit' => [
                'required',
                'string',
                'max:50'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Название продукта обязательно для заполнения',
            'name.unique' => 'Продукт с таким названием уже существует',
            'name.max' => 'Название продукта не может превышать 255 символов',
            'type.required' => 'Тип продукта обязателен для заполнения',
            'type.in' => 'Тип продукта должен быть: product или material',
            'unit.required' => 'Единица измерения обязательна для заполнения',
            'unit.max' => 'Единица измерения не может превышать 50 символов',
        ];
    }
    public function attributes(): array
    {
        return [
            'name' => 'название продукта',
            'type' => 'тип продукта',
            'unit' => 'единица измерения',
        ];
    }
}
