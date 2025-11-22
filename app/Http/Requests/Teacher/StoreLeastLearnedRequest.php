<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeastLearnedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('teacher');
    }

    public function rules(): array
    {
        return [
            'quarter' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'exam_title' => ['nullable', 'string', 'max:150'],
            'total_students' => ['required', 'integer', 'min:1', 'max:200'],
            'total_items' => ['required', 'integer', 'min:1', 'max:200'],
            'categories_payload' => ['required', 'string'],
        ];
    }
}
