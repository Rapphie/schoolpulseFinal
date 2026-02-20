<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportClassRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && (int) $user->role_id === 2;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'quarter' => ['nullable', 'integer', Rule::in([1, 2, 3, 4])],
        ];
    }
}
