<?php

namespace Assetplan\Dispatcher\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsIlluminateJob implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!class_exists($value)) {
            $fail('Job class does not exist');
        }

        if (!method_exists($value, 'handle')) {
            $fail('Job class does not have a handle method');
        }

        if (!is_subclass_of($value, 'Illuminate\Contracts\Queue\ShouldQueue')) {
            $fail('Job class does not implement Illuminate\Contracts\Queue\ShouldQueue');
        }
    }
}
