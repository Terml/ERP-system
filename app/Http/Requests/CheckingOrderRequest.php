<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckingOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('master');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'materials_used' => [
                'nullable',
                'array'
            ],
            'materials_used.*.product_id' => [
                'required_with:materials_used',
                'integer',
                'exists:products,id'
            ],
            'materials_used.*.quantity_used' => [
                'required_with:materials_used',
                'integer',
                'min:1'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'materials_used.*.product_id.required_with' => 'ID продукта обязателен при указании использованных материалов',
            'materials_used.*.product_id.exists' => 'Указанный продукт не существует',
            'materials_used.*.quantity_used.required_with' => 'Количество использованного материала обязательно',
            'materials_used.*.quantity_used.min' => 'Количество использованного материала должно быть больше 0',
        ];
    }
    public function attributes(): array
    {
        return [
            'inspection_notes' => 'примечания для проверки',
            'quality_self_assessment' => 'самооценка качества',
            'completion_percentage' => 'процент выполнения',
            'materials_used' => 'использованные материалы',
            'materials_used.*.product_id' => 'ID продукта',
            'materials_used.*.quantity_used' => 'количество использованного материала',
            'issues_encountered' => 'проблемы при выполнении',
            'estimated_completion_time' => 'оценочное время завершения'
        ];
    }
    protected function prepareForValidation(): void
    {
        // Можно добавить предварительную обработку данных
        if ($this->has('completion_percentage') && $this->input('completion_percentage') > 100) {
            $this->merge([
                'completion_percentage' => 100
            ]);
        }
    }
}
