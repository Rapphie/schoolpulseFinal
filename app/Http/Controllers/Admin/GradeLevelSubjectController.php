<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeLevelSubjectRequest;
use App\Http\Requests\Admin\UpdateGradeLevelSubjectRequest;
use App\Models\GradeLevelSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GradeLevelSubjectController extends Controller
{
    public function store(StoreGradeLevelSubjectRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $gradeLevelSubject = GradeLevelSubject::firstOrNew([
            'grade_level_id' => $validated['grade_level_id'],
            'subject_id' => $validated['subject_id'],
        ]);

        if (! $gradeLevelSubject->exists) {
            $gradeLevelSubject->fill([
                'is_active' => true,
                'written_works_weight' => 40,
                'performance_tasks_weight' => 40,
                'quarterly_assessments_weight' => 20,
            ]);
        } elseif (! $gradeLevelSubject->is_active) {
            $gradeLevelSubject->is_active = true;
        }

        $gradeLevelSubject->save();

        return $this->respond(
            $request,
            'Subject assigned successfully.',
            $gradeLevelSubject,
            ['grade_level' => $validated['grade_level_id']]
        );
    }

    public function update(
        UpdateGradeLevelSubjectRequest $request,
        GradeLevelSubject $gradeLevelSubject
    ): JsonResponse|RedirectResponse {
        $validated = $request->validated();

        $gradeLevelSubject->update($validated);

        return $this->respond(
            $request,
            'Subject assignment updated successfully.',
            $gradeLevelSubject->fresh(),
            ['grade_level' => $gradeLevelSubject->grade_level_id]
        );
    }

    public function destroy(Request $request, GradeLevelSubject $gradeLevelSubject): JsonResponse|RedirectResponse
    {
        $gradeLevelSubject->update(['is_active' => false]);

        return $this->respond(
            $request,
            'Subject assignment deactivated successfully.',
            $gradeLevelSubject->fresh(),
            ['grade_level' => $gradeLevelSubject->grade_level_id]
        );
    }

    private function respond(
        Request $request,
        string $message,
        GradeLevelSubject $gradeLevelSubject,
        array $query = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'message' => $message,
                'grade_level_subject' => $gradeLevelSubject,
            ]);
        }

        return redirect()
            ->route('admin.subjects.index', $query)
            ->with('success', $message);
    }
}
