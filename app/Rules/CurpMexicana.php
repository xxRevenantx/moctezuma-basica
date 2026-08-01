<?php

namespace App\Rules;

use App\Services\CurpLocalLookupService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CurpMexicana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $resultado = app(CurpLocalLookupService::class)->validarFormato((string) $value);

        if (! $resultado['valida']) {
            $fail($resultado['mensaje']);
        }
    }
}
