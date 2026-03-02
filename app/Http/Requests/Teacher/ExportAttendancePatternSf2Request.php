<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class ExportAttendancePatternSf2Request extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && (int) $user->role_id === 2;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'month' => ['required', 'date_format:Y-m'],
            'school_id' => ['nullable', 'string', 'max:50'],
            'school_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_id.required' => 'Section is required for SF2 export.',
            'month.required' => 'Month is required for SF2 export.',
            'month.date_format' => 'Month must be in Y-m format (e.g., 2025-06).',
        ];
    }
}
