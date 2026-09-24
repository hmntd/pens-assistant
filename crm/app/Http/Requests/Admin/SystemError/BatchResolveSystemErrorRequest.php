<?php

namespace App\Http\Requests\Admin\SystemError;

use Illuminate\Foundation\Http\FormRequest;

class BatchResolveSystemErrorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && ($user->is_admin || $user->hasRole('admin')));
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:system_error_logs,id'],
            'is_resolved' => ['required', 'boolean'],
        ];
    }
}
