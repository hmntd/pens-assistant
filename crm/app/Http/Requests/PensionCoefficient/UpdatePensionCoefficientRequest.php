<?php

namespace App\Http\Requests\PensionCoefficient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePensionCoefficientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'coefficient' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
