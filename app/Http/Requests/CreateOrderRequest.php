<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\DeadlineRule;

class CreateOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('manager');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id'
            ],
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
            'deadline' => [
                'required',
                'date',
                new DeadlineRule()
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'company_id.required' => 'ID компании обязателен для заполнения',
            'company_id.exists' => 'Выбранная компания не существует',
            'product_id.required' => 'ID продукта обязателен для заполнения',
            'product_id.exists' => 'Выбранный продукт не существует',
            'quantity.required' => 'Количество обязательно для заполнения',
            'quantity.min' => 'Количество должно быть больше 0',
            'quantity.max' => 'Количество не может превышать 10000',
            'deadline.required' => 'Срок выполнения обязателен',
        ];
    }
    public function attributes(): array
    {
        return [
            'company_id' => 'компания',
            'product_id' => 'продукт',
            'quantity' => 'количество',
            'deadline' => 'срок выполнения',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->has('deadline')) {
            $this->merge([
                'deadline' => $this->input('deadline') . ' 23:59:59'
            ]);
        }
    }
}
