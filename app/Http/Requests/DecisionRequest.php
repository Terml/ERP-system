<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('otk');
    }
    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                'in:accept,reject'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'decision.required' => 'Решение ОТК обязательно',
            'decision.in' => 'Решение должно быть: accept или reject',
        ];
    }
    public function attributes(): array
    {
        return [
            'decision' => 'решение',
        ];
    }
}
