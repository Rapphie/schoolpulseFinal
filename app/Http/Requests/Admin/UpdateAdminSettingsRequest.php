<?php

namespace App\Http\Requests\Admin;

use App\Models\GradeLevelSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        $rules = [
            'panel' => ['required', 'in:teacher_enrollment,assessment_weights,school_year_month_days'],
        ];

        return match ($this->input('panel')) {
            'teacher_enrollment' => $rules + [
                'teacher_enrollment' => ['nullable', 'boolean'],
            ],
            'assessment_weights' => $rules + [
                'weights' => ['nullable', 'array'],
                'weights.*.written_works_weight' => ['required', 'integer', 'between:0,100'],
                'weights.*.performance_tasks_weight' => ['required', 'integer', 'between:0,100'],
                'weights.*.quarterly_assessments_weight' => ['required', 'integer', 'between:0,100'],
            ],
            'school_year_month_days' => $rules + [
                'school_days' => ['nullable', 'array'],
                'school_days.*' => ['nullable', 'integer', 'between:0,31'],
            ],
            default => $rules,
        };
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('panel') !== 'assessment_weights') {
                    return;
                }

                /** @var array<int|string, array<string, int|string|null>> $weights */
                $weights = $this->input('weights', []);

                foreach ($weights as $gradeLevelSubjectId => $weightSet) {
                    $writtenWorks = (int) ($weightSet['written_works_weight'] ?? 0);
                    $performanceTasks = (int) ($weightSet['performance_tasks_weight'] ?? 0);
                    $quarterlyAssessments = (int) ($weightSet['quarterly_assessments_weight'] ?? 0);
                    $total = $writtenWorks + $performanceTasks + $quarterlyAssessments;

                    if ($total === 100) {
                        continue;
                    }

                    $gradeLevelSubject = GradeLevelSubject::with(['gradeLevel', 'subject'])
                        ->find($gradeLevelSubjectId);

                    $label = $gradeLevelSubject
                        ? "{$gradeLevelSubject->subject?->name} ({$gradeLevelSubject->gradeLevel?->name})"
                        : "subject assignment {$gradeLevelSubjectId}";

                    $validator->errors()->add(
                        "weights.{$gradeLevelSubjectId}",
                        "Assessment weights for {$label} must total 100%. Currently: {$total}%."
                    );
                }
            },
        ];
    }
}
