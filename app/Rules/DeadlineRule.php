<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DeadlineRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('Срок выполнения обязателен для заполнения.');
            return;
        }
        $deadline = is_string($value) ? strtotime($value) : $value;
        if ($deadline === false) {
            $fail('Некорректный формат даты.');
            return;
        }
        if ($deadline <= time()) {
            $fail('Срок выполнения должен быть в будущем.');
            return;
        }
        $maxDeadline = strtotime('+2 years');
        if ($deadline > $maxDeadline) {
            $fail('Срок выполнения не может быть более чем через 2 года.');
            return;
        }
    }
}
