<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('otk');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
    public function messages(): array
    {
        return [];
    }
    public function attributes(): array
    {
        return [];
    }
}