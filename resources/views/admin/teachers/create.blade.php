@extends('base')

@section('title', 'Add Teacher')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Teacher</li>
                </ol>
            </nav>
            <a href="{{ route('admin.teachers.index') }}"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-decoration-none">Back to
                Teachers</a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form id="addTeacherForm" action="{{ route('admin.teachers.store') }}" method="POST"
                    enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    <div class="col-md-6">
                        <label for="qualification" class="form-label">Qualification</label>
                        <input type="text" class="form-control" id="qualification" name="qualification" required>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="on-leave">On Leave</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="sectionAssignments" class="form-label">Section Advisory</label>
                        @php
                            // Prepare previous selections and grade/section mappings
                            $sectionSelections = old('section_ids', [null]);
                            if (empty($sectionSelections)) {
                                $sectionSelections = [null];
                            }

                            $gradeLevels = $sections
                                ->map(function ($s) {
                                    return optional($s->gradeLevel);
                                })
                                ->filter()
                                ->unique('id')
                                ->values();

                            $gradeOptions = [];
                            foreach ($gradeLevels as $g) {
                                $gradeOptions[$g->id] = $g->name ?? 'Grade ' . $g->level;
                            }

                            $sectionsByGrade = [];
                            foreach ($sections as $s) {
                                $gid = optional($s->gradeLevel)->id ?? 0;
                                if (!isset($sectionsByGrade[$gid])) {
                                    $sectionsByGrade[$gid] = [];
                                }
                                $sectionsByGrade[$gid][] = ['id' => $s->id, 'name' => $s->name];
                            }
                        @endphp
                        <div id="sectionAssignments" class="d-flex flex-column gap-2">
                            @foreach ($sectionSelections as $selectedSection)
                                @php
                                    $selectedSectionObj = $sections->firstWhere('id', $selectedSection);
                                    $selectedGradeId = optional(optional($selectedSectionObj)->gradeLevel)->id ?? '';
                                @endphp
                                <div class="section-assignment d-flex flex-column flex-md-row gap-2">
                                    <select name="grade_ids[]" class="form-select grade-select">
                                        <option value="">Select Grade</option>
                                        @foreach ($gradeOptions as $gid => $glabel)
                                            <option value="{{ $gid }}"
                                                {{ (string) $selectedGradeId === (string) $gid ? 'selected' : '' }}>
                                                {{ $glabel }}</option>
                                        @endforeach
                                    </select>

                                    <select name="section_ids[]" class="form-select section-select">
                                        <option value="">Select Section</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}"
                                                {{ (string) $selectedSection === (string) $section->id ? 'selected' : '' }}
                                                data-grade-id="{{ optional($section->gradeLevel)->id ?? 0 }}">
                                                {{ $section->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button"
                                        class="btn btn-outline-danger remove-section {{ $loop->first ? 'd-none' : '' }}">Remove</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" id="addSectionButton" class="btn btn-outline-primary btn-sm mt-2">Add
                            Advisory Section</button>
                        <div class="form-text">Use the button to append additional advisory sections as needed.</div>
                        @error('section_ids.*')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        <template id="sectionSelectTemplate">
                            <div class="section-assignment d-flex flex-column flex-md-row gap-2">
                                <select name="grade_ids[]" class="form-select grade-select">
                                    <option value="">Select Grade</option>
                                    @foreach ($gradeOptions as $gid => $glabel)
                                        <option value="{{ $gid }}">{{ $glabel }}</option>
                                    @endforeach
                                </select>

                                <select name="section_ids[]" class="form-select section-select">
                                    <option value="">Select Section</option>
                                </select>
                                <button type="button" class="btn btn-outline-danger remove-section">Remove</button>
                            </div>
                        </template>
                    </div>
                    <div class="col-12">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Teacher</button>
                        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sectionContainer = document.getElementById('sectionAssignments');
            const addSectionButton = document.getElementById('addSectionButton');
            const template = document.getElementById('sectionSelectTemplate');
            const sectionsByGrade = @json($sectionsByGrade);

            if (!sectionContainer || !addSectionButton || !template) {
                return;
            }

            const updateRemoveButtons = () => {
                const groups = sectionContainer.querySelectorAll('.section-assignment');
                groups.forEach((group, index) => {
                    const removeButton = group.querySelector('.remove-section');
                    if (!removeButton) {
                        return;
                    }
                    removeButton.classList.toggle('d-none', groups.length === 1);
                });
            };

            const populateSectionOptions = (sectionSelect, gradeId, selectedSectionId = null) => {
                // Clear existing
                sectionSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select Section';
                sectionSelect.appendChild(placeholder);

                const list = sectionsByGrade[gradeId] || [];
                list.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    if (selectedSectionId && String(selectedSectionId) === String(s.id)) {
                        opt.selected = true;
                    }
                    sectionSelect.appendChild(opt);
                });
            };

            addSectionButton.addEventListener('click', () => {
                const clone = template.content.cloneNode(true);
                sectionContainer.appendChild(clone);
                updateRemoveButtons();
            });

            // When grade changes, filter the section select in the same row
            sectionContainer.addEventListener('change', (event) => {
                const gradeSelect = event.target.closest('.grade-select');
                if (gradeSelect) {
                    const group = gradeSelect.closest('.section-assignment');
                    const sectionSelect = group.querySelector('.section-select');
                    populateSectionOptions(sectionSelect, gradeSelect.value);
                }
            });

            // Initialize rows: for each existing row set section options according to selected grade
            sectionContainer.querySelectorAll('.section-assignment').forEach(group => {
                const gradeSelect = group.querySelector('.grade-select');
                const sectionSelect = group.querySelector('.section-select');
                // If the section select already has an option selected (because old input), use it
                const preSelected = sectionSelect.value || null;
                if (gradeSelect && sectionSelect) {
                    // If grade is selected, populate based on it; otherwise try to infer from existing option data-grade-id
                    if (gradeSelect.value) {
                        populateSectionOptions(sectionSelect, gradeSelect.value, preSelected);
                    } else {
                        // infer grade from existing selected option's data attribute (handle checked/selected)
                        const existingOpt = sectionSelect.querySelector('option[selected], option:checked');
                        const dataGrade = existingOpt ? existingOpt.getAttribute('data-grade-id') : null;
                        if (dataGrade) {
                            gradeSelect.value = dataGrade;
                            populateSectionOptions(sectionSelect, dataGrade, preSelected);
                        }
                    }
                }
            });

            sectionContainer.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.remove-section');
                if (!removeButton) {
                    return;
                }
                const group = removeButton.closest('.section-assignment');
                if (group) {
                    group.remove();
                    updateRemoveButtons();
                }
            });

            updateRemoveButtons();
        });
    </script>
@endpush
