<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubsistenceMinimumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => 'required|integer|between:1950,2099',
            'for_disabled_persons' => 'required|numeric|min:0',
            'general_minimum' => 'required|numeric|min:0',
        ];
    }
}
