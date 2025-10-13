<?php

namespace App\Http\Requests;

class CreateProductionTaskWithComponentsRequest extends BaseRequest
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
            'components' => [
                'required',
                'array',
                'min:1'
            ],
            'components.*.product_id' => [
                'required',
                'integer',
                'exists:products,id'
            ],
            'components.*.quantity' => [
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
            'order_id.required' => 'ID заказа обязателен для заполнения',
            'order_id.exists' => 'Выбранный заказ не существует',
            'quantity.required' => 'Количество обязательно для заполнения',
            'quantity.min' => 'Количество должно быть больше 0',
            'quantity.max' => 'Количество не может превышать 1000',
            'user_id.exists' => 'Выбранный пользователь не существует',
            'components.required' => 'Необходимо указать хотя бы один компонент',
            'components.min' => 'Необходимо указать хотя бы один компонент',
            'components.*.product_id.required' => 'ID продукта обязателен для каждого компонента',
            'components.*.product_id.exists' => 'Указанный продукт не существует',
            'components.*.quantity.required' => 'Количество компонента обязательно',
            'components.*.quantity.min' => 'Количество компонента должно быть больше 0',
            'components.*.quantity.max' => 'Количество компонента не может превышать 10000',
        ];
    }
    public function attributes(): array
    {
        return [
            'order_id' => 'заказ',
            'quantity' => 'количество',
            'user_id' => 'пользователь',
            'components' => 'компоненты',
            'components.*.product_id' => 'продукт',
            'components.*.quantity' => 'количество',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->has('components')) {
            $components = array_filter($this->input('components'), function ($component) {
                return !empty($component['product_id']) && !empty($component['quantity']);
            });
            $this->merge(['components' => array_values($components)]);
        }
    }
}
