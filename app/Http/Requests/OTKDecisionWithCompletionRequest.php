<?php

namespace App\Http\Requests;

class OTKDecisionWithCompletionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('otk');
    }
    public function rules(): array
    {
        return [
            'otk_user_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'decision' => [
                'required',
                'string',
                'in:accepted,rejected'
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'otk_user_id.required' => 'ID пользователя ОТК обязателен',
            'otk_user_id.exists' => 'Указанный пользователь ОТК не существует',
            'decision.required' => 'Решение ОТК обязательно',
            'decision.in' => 'Решение должно быть "accepted" или "rejected"',
        ];
    }
    public function attributes(): array
    {
        return [
            'otk_user_id' => 'пользователь ОТК',
            'decision' => 'решение',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->has('otk_user_id')) {
            $this->merge(['otk_user_id' => $this->user()->id]);
        }
    }
}
