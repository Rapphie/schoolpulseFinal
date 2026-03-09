<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGradeLevelSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'written_works_weight' => ['sometimes', 'integer', 'between:0,100'],
            'performance_tasks_weight' => ['sometimes', 'integer', 'between:0,100'],
            'quarterly_assessments_weight' => ['sometimes', 'integer', 'between:0,100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny([
                    'written_works_weight',
                    'performance_tasks_weight',
                    'quarterly_assessments_weight',
                ])) {
                    return;
                }

                $gradeLevelSubject = $this->route('gradeLevelSubject');

                if (! $gradeLevelSubject) {
                    return;
                }

                $writtenWorks = (int) $this->input(
                    'written_works_weight',
                    $gradeLevelSubject->written_works_weight
                );
                $performanceTasks = (int) $this->input(
                    'performance_tasks_weight',
                    $gradeLevelSubject->performance_tasks_weight
                );
                $quarterlyAssessments = (int) $this->input(
                    'quarterly_assessments_weight',
                    $gradeLevelSubject->quarterly_assessments_weight
                );

                $total = $writtenWorks + $performanceTasks + $quarterlyAssessments;

                if ($total !== 100) {
                    $validator->errors()->add(
                        'written_works_weight',
                        "Assessment weights must total 100%. Currently: {$total}%."
                    );
                }
            },
        ];
    }
}
