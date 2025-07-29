<div>
    <form wire:submit.prevent="store">
        <div class="mb-3">
            <label for="grade_level_id" class="form-label">Assign to Grade Level <span
                    class="text-danger">*</span></label>
            <select class="form-control" wire:model="selectedGradeLevel" id="grade_level_id" name="grade_level_id" required>
                <option value="" disabled selected>-- Select a Grade Level --</option>
                @foreach ($gradeLevels as $gradeLevel)
                    <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                @endforeach
            </select>
        </div>

        @if (!empty($previewSubjects))
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewSubjects as $subject)
                            <tr>
                                <td>{{ $subject['name'] }}</td>
                                <td>{{ $subject['code'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary d-flex align-items-center" data-bs-dismiss="modal">
                <i data-feather="x" class="me-2"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary d-flex align-items-center">
                <i data-feather="save" class="me-2"></i> Save Subjects
            </button>
        </div>
    </form>
</div>