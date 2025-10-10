<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TakeTaskRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('master');
    }
    public function rules(): array
    {
        return [
            'estimated_completion_time' => [
                'nullable',
                'integer',
                'min:1',
                'max:8760' // максимум год в часах
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'estimated_completion_time.min' => 'Время выполнения должно быть больше 0 часов',
            'estimated_completion_time.max' => 'Время выполнения не может превышать год',
        ];
    }
    public function attributes(): array
    {
        return [
            'estimated_completion_time' => 'предполагаемое время выполнения',
        ];
    }
}
