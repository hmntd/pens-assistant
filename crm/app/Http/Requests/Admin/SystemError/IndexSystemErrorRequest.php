<?php

namespace App\Http\Requests\Admin\SystemError;

use Illuminate\Foundation\Http\FormRequest;

class IndexSystemErrorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && ($user->is_admin || $user->hasRole('admin')));
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:all,unresolved,resolved'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
