<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('manager');
    }
    public function rules(): array
    {
        $productId = $this->route('id');
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                "unique:products,name,{$productId}"
            ],
            'type' => [
                'sometimes',
                'string',
                'in:product,material'
            ],
            'unit' => [
                'sometimes',
                'string',
                'max:50'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Продукт с таким названием уже существует',
            'name.max' => 'Название продукта не может превышать 255 символов',
            'type.in' => 'Тип продукта должен быть: product или material',
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
