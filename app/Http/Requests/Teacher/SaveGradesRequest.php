<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class SaveGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && (int) $user->role_id === 2;
    }

    public function rules(): array
    {
        return [
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.student_id' => ['required', 'exists:students,id'],
            'grades.*.assessment_id' => ['required', 'exists:assessments,id'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required' => 'At least one grade entry is required.',
            'grades.array' => 'Grades payload must be an array.',
            'grades.min' => 'At least one grade entry is required.',
            'grades.*.student_id.required' => 'Student ID is required for every grade entry.',
            'grades.*.student_id.exists' => 'One or more selected students were not found.',
            'grades.*.assessment_id.required' => 'Assessment ID is required for every grade entry.',
            'grades.*.assessment_id.exists' => 'One or more selected assessments were not found.',
            'grades.*.score.numeric' => 'Scores must be numeric values.',
            'grades.*.score.min' => 'Scores cannot be negative.',
            'grades.*.score.max' => 'Scores cannot be greater than 1000.',
        ];
    }
}
