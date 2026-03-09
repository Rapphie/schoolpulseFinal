<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeLevelSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('grade_level_subjects', 'subject_id')
                    ->where(fn ($query) => $query->where('grade_level_id', $this->integer('grade_level_id'))),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.unique' => 'This subject is already assigned to the selected grade level.',
        ];
    }
}
