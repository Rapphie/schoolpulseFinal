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
                {{-- Validation Errors Summary --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i data-feather="alert-triangle" class="icon-sm me-2"></i> Please fix the following
                            errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form id="addTeacherForm" action="{{ route('admin.teachers.store') }}" method="POST"
                    enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="firstName"
                            name="first_name" value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="lastName"
                            name="last_name" value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone"
                            name="phone" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Sex <span class="text-danger">*</span> </label>
                        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of Birth </label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                            id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Address</span></label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address"
                            name="address" value="{{ old('address') }}">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="qualification" class="form-label">Qualification </label>
                        <input type="text" class="form-control @error('qualification') is-invalid @enderror"
                            id="qualification" name="qualification" value="{{ old('qualification') }}">
                        @error('qualification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="on-leave" {{ old('status') == 'on-leave' ? 'selected' : '' }}>On Leave</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                            @foreach ($sectionSelections as $index => $selectedSection)
                                @php
                                    $selectedSectionObj = $sections->firstWhere('id', $selectedSection);
                                    $selectedGradeId = optional(optional($selectedSectionObj)->gradeLevel)->id ?? '';
                                    $hasError = $errors->has("section_ids.{$index}");
                                @endphp
                                <div class="section-assignment d-flex flex-column gap-2">
                                    <div class="d-flex flex-column flex-md-row gap-2">
                                        <select name="grade_ids[]" class="form-select grade-select">
                                            <option value="">Select Grade</option>
                                            @foreach ($gradeOptions as $gid => $glabel)
                                                <option value="{{ $gid }}"
                                                    {{ (string) $selectedGradeId === (string) $gid ? 'selected' : '' }}>
                                                    {{ $glabel }}</option>
                                            @endforeach
                                        </select>

                                        <div class="dropdown w-100">
                                            <input type="text"
                                                class="form-control section-input {{ $hasError ? 'is-invalid' : '' }}"
                                                name="section_names[]" placeholder="Search Section" autocomplete="off"
                                                value="{{ old("section_names.{$index}", optional($selectedSectionObj)->name) }}">
                                            <input type="hidden" name="section_ids[]" class="section-id"
                                                value="{{ $selectedSection }}">
                                            <div class="dropdown-menu w-100 section-menu"></div>
                                        </div>
                                        <button type="button"
                                            class="btn btn-outline-danger remove-section {{ $loop->first ? 'd-none' : '' }}">Remove</button>
                                    </div>
                                    @if ($hasError)
                                        <div class="text-danger small">
                                            <i data-feather="alert-circle" class="icon-xs me-1"></i>
                                            {{ $errors->first("section_ids.{$index}") }}
                                        </div>
                                    @endif
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

                                <div class="dropdown w-100">
                                    <input type="text" class="form-control section-input" name="section_names[]"
                                        placeholder="Search Section" autocomplete="off">
                                    <input type="hidden" name="section_ids[]" class="section-id" value="">
                                    <div class="dropdown-menu w-100 section-menu"></div>
                                </div>
                                <button type="button" class="btn btn-outline-danger remove-section">Remove</button>
                            </div>
                        </template>
                    </div>
                    <div class="col-12">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control @error('profile_picture') is-invalid @enderror"
                            id="profile_picture" name="profile_picture" accept="image/*">
                        @error('profile_picture')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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

            const getSectionsForGrade = (gradeId) => {
                return sectionsByGrade[gradeId] || [];
            };

            const showMenu = (group) => {
                const menu = group.querySelector('.section-menu');
                if (!menu) return;
                menu.classList.add('show');
            };

            const hideMenu = (group) => {
                const menu = group.querySelector('.section-menu');
                if (!menu) return;
                menu.classList.remove('show');
            };

            const renderMenu = (group, items, query = '') => {
                const menu = group.querySelector('.section-menu');
                if (!menu) return;

                const q = (query || '').trim().toLowerCase();
                const filtered = q ?
                    items.filter(s => String(s.name).toLowerCase().includes(q)) :
                    items;

                menu.innerHTML = '';

                if (!filtered.length) {
                    const empty = document.createElement('div');
                    empty.className = 'dropdown-item text-muted';
                    empty.textContent = items.length ? 'No matches' : 'Select a grade first';
                    menu.appendChild(empty);
                    return;
                }

                filtered.forEach(s => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'dropdown-item';
                    btn.textContent = s.name;
                    btn.dataset.id = s.id;
                    btn.dataset.name = s.name;
                    menu.appendChild(btn);
                });
            };

            addSectionButton.addEventListener('click', () => {
                const clone = template.content.cloneNode(true);
                sectionContainer.appendChild(clone);
                const newGroup = sectionContainer.lastElementChild;
                if (newGroup) {
                    const gradeSelect = newGroup.querySelector('.grade-select');
                    const items = gradeSelect && gradeSelect.value ? getSectionsForGrade(gradeSelect
                        .value) : [];
                    renderMenu(newGroup, items);
                }
                updateRemoveButtons();
            });

            // When grade changes, filter the section select in the same row
            sectionContainer.addEventListener('change', (event) => {
                const gradeSelect = event.target.closest('.grade-select');
                if (gradeSelect) {
                    const group = gradeSelect.closest('.section-assignment');
                    const sectionInput = group.querySelector('.section-input');
                    const sectionHidden = group.querySelector('.section-id');

                    if (sectionInput) sectionInput.value = '';
                    if (sectionHidden) sectionHidden.value = '';

                    const items = gradeSelect.value ? getSectionsForGrade(gradeSelect.value) : [];
                    renderMenu(group, items, sectionInput ? sectionInput.value : '');
                    showMenu(group);
                }
            });

            sectionContainer.addEventListener('focusin', (event) => {
                const sectionInput = event.target.closest('.section-input');
                if (!sectionInput) return;
                const group = sectionInput.closest('.section-assignment');
                const gradeSelect = group.querySelector('.grade-select');
                const items = gradeSelect && gradeSelect.value ? getSectionsForGrade(gradeSelect.value) :
            [];
                renderMenu(group, items, sectionInput.value);
                showMenu(group);
            });

            sectionContainer.addEventListener('input', (event) => {
                const sectionInput = event.target.closest('.section-input');
                if (!sectionInput) return;
                const group = sectionInput.closest('.section-assignment');
                const sectionHidden = group.querySelector('.section-id');
                if (sectionHidden) sectionHidden.value = '';

                const gradeSelect = group.querySelector('.grade-select');
                const items = gradeSelect && gradeSelect.value ? getSectionsForGrade(gradeSelect.value) :
            [];
                renderMenu(group, items, sectionInput.value);
                showMenu(group);
            });

            sectionContainer.addEventListener('click', (event) => {
                const item = event.target.closest('.section-menu .dropdown-item');
                if (!item) return;
                const group = item.closest('.section-assignment');
                const sectionInput = group.querySelector('.section-input');
                const sectionHidden = group.querySelector('.section-id');

                const id = item.dataset.id || '';
                const name = item.dataset.name || item.textContent || '';
                if (sectionInput) sectionInput.value = name;
                if (sectionHidden) sectionHidden.value = id;
                hideMenu(group);
            });

            // Initialize rows: for each existing row set section options according to selected grade
            sectionContainer.querySelectorAll('.section-assignment').forEach(group => {
                const gradeSelect = group.querySelector('.grade-select');
                const sectionHidden = group.querySelector('.section-id');
                const preSelectedId = sectionHidden ? (sectionHidden.value || null) : null;

                const items = gradeSelect && gradeSelect.value ? getSectionsForGrade(gradeSelect.value) :
            [];
                renderMenu(group, items);

                if (preSelectedId && items.length) {
                    const selected = items.find(s => String(s.id) === String(preSelectedId));
                    const sectionInput = group.querySelector('.section-input');
                    if (selected && sectionInput) {
                        sectionInput.value = selected.name;
                    }
                }
            });

            sectionContainer.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.remove-section');
                if (removeButton) {
                    const group = removeButton.closest('.section-assignment');
                    if (group) {
                        group.remove();
                        updateRemoveButtons();
                    }
                    return;
                }
            });

            document.addEventListener('click', (event) => {
                if (!sectionContainer.contains(event.target)) {
                    sectionContainer.querySelectorAll('.section-assignment').forEach(hideMenu);
                    return;
                }

                const clickedGroup = event.target.closest('.section-assignment');
                sectionContainer.querySelectorAll('.section-assignment').forEach(group => {
                    if (group !== clickedGroup) {
                        hideMenu(group);
                    }
                });
            });

            updateRemoveButtons();
        });
    </script>
@endpush
