@extends('base')

@section('title', 'Enroll New Student')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/students/students.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.sections.index') }}">Sections</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.sections.manage', $section) }}">{{ $section->name }}</a>
                        </li>
                        <li class="breadcrumb-item active">Enroll New Student</li>
                    </ol>
                </nav>
                <h4 class="mb-0">Enroll New Student in {{ $section->name }}</h4>
                <small class="text-muted">{{ $section->gradeLevel->name ?? '' }} &mdash; {{ $class->schoolYear->name ?? '' }}</small>
            </div>
        </div>

        @if (!$activeSchoolYear)
            <div class="alert alert-warning">
                <i data-feather="alert-circle" class="icon-sm me-2"></i>
                <strong>No Active School Year</strong><br>
                Please ensure there is an active school year before enrolling students.
            </div>
        @else
            <form action="{{ route('admin.enrollment.store', $class) }}" method="POST" id="enrollStudentForm">
                @csrf

                <div class="row">
                    <!-- Student Information -->
                    <div class="col-lg-8">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="user" class="icon-sm me-2"></i>
                                    Student Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                            id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                            id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lrn" class="form-label">LRN (Learner Reference Number)</label>
                                        <input type="text" class="form-control @error('lrn') is-invalid @enderror"
                                            id="lrn" name="lrn" value="{{ old('lrn') }}" maxlength="12">
                                        @error('lrn')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Optional - 12-digit LRN</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('gender') is-invalid @enderror" id="gender"
                                            name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male
                                            </option>
                                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>
                                                Female</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="birthdate" class="form-label">Birthdate <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('birthdate') is-invalid @enderror"
                                            id="birthdate" name="birthdate" value="{{ old('birthdate') }}" required>
                                        @error('birthdate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Capacity info -->
                                        <label class="form-label">Class Capacity</label>
                                        <div class="form-control-plaintext">
                                            <span class="badge {{ $enrolledCount >= $class->capacity ? 'bg-danger' : 'bg-success' }}">
                                                {{ $enrolledCount }} / {{ $class->capacity }}
                                            </span>
                                            enrolled
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information (Analytics) -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="info" class="icon-sm me-2"></i>
                                    Additional Information
                                </h6>
                                <small class="text-muted">Optional - Used for analytics</small>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="distance_km" class="form-label">Distance from School (km)</label>
                                        <input type="number"
                                            class="form-control @error('distance_km') is-invalid @enderror"
                                            id="distance_km" name="distance_km" value="{{ old('distance_km') }}"
                                            step="0.1" min="0" max="100">
                                        @error('distance_km')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="transportation" class="form-label">Mode of Transportation</label>
                                        <select class="form-select @error('transportation') is-invalid @enderror"
                                            id="transportation" name="transportation">
                                            <option value="">Select</option>
                                            <option value="Walk"
                                                {{ old('transportation') === 'Walk' ? 'selected' : '' }}>Walk
                                            </option>
                                            <option value="Bicycle"
                                                {{ old('transportation') === 'Bicycle' ? 'selected' : '' }}>Bicycle
                                            </option>
                                            <option value="Motorcycle"
                                                {{ old('transportation') === 'Motorcycle' ? 'selected' : '' }}>
                                                Motorcycle</option>
                                            <option value="Tricycle"
                                                {{ old('transportation') === 'Tricycle' ? 'selected' : '' }}>
                                                Tricycle</option>
                                            <option value="Jeepney"
                                                {{ old('transportation') === 'Jeepney' ? 'selected' : '' }}>
                                                Jeepney</option>
                                            <option value="Bus"
                                                {{ old('transportation') === 'Bus' ? 'selected' : '' }}>Bus
                                            </option>
                                            <option value="Private Vehicle"
                                                {{ old('transportation') === 'Private Vehicle' ? 'selected' : '' }}>
                                                Private Vehicle</option>
                                        </select>
                                        @error('transportation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="family_income" class="form-label">Socioeconomic Status</label>
                                        <select class="form-select @error('family_income') is-invalid @enderror"
                                            id="family_income" name="family_income">
                                            <option value="">Select</option>
                                            <option value="Low" {{ old('family_income') === 'Low' ? 'selected' : '' }}>
                                                Low</option>
                                            <option value="Medium"
                                                {{ old('family_income') === 'Medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="High"
                                                {{ old('family_income') === 'High' ? 'selected' : '' }}>
                                                High</option>
                                        </select>
                                        @error('family_income')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Information -->
                    <div class="col-lg-4">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="users" class="icon-sm me-2"></i>
                                    Guardian Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="use_existing_guardian" name="use_existing_guardian" value="1"
                                                {{ old('use_existing_guardian') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="use_existing_guardian">
                                                Use Existing Guardian
                                            </label>
                                        </div>
                                        <input type="hidden" id="guardian_id" name="guardian_id"
                                            value="{{ old('guardian_id') }}">
                                    </div>
                                    <div class="col-12 d-none" id="existingGuardianSearchContainer">
                                        <label for="existing_guardian_search" class="form-label">Search Existing
                                            Guardian</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" id="existing_guardian_search"
                                                placeholder="Search for a guardian..." autocomplete="off">
                                            <div class="dropdown-menu w-100" id="existing_guardian_dropdown"></div>
                                        </div>
                                        <small class="text-muted">Type at least 2 characters to search guardians.</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_first_name" class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('guardian_first_name') is-invalid @enderror"
                                            id="guardian_first_name" name="guardian_first_name"
                                            value="{{ old('guardian_first_name') }}" required>
                                        @error('guardian_first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_last_name" class="form-label">Last Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('guardian_last_name') is-invalid @enderror"
                                            id="guardian_last_name" name="guardian_last_name"
                                            value="{{ old('guardian_last_name') }}" required>
                                        @error('guardian_last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_relationship" class="form-label">Relationship <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('guardian_relationship') is-invalid @enderror"
                                            id="guardian_relationship" name="guardian_relationship" required>
                                            <option value="">Select Relationship</option>
                                            <option value="parent"
                                                {{ old('guardian_relationship') === 'parent' ? 'selected' : '' }}>Parent
                                            </option>
                                            <option value="sibling"
                                                {{ old('guardian_relationship') === 'sibling' ? 'selected' : '' }}>
                                                Sibling</option>
                                            <option value="relative"
                                                {{ old('guardian_relationship') === 'relative' ? 'selected' : '' }}>
                                                Relative</option>
                                            <option value="guardian"
                                                {{ old('guardian_relationship') === 'guardian' ? 'selected' : '' }}>
                                                Guardian</option>
                                        </select>
                                        @error('guardian_relationship')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_email" class="form-label">Email <span
                                                class="text-danger">*</span></label>
                                        <input type="email"
                                            class="form-control @error('guardian_email') is-invalid @enderror"
                                            id="guardian_email" name="guardian_email"
                                            value="{{ old('guardian_email') }}" required>
                                        @error('guardian_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div id="guardianSearchStatus" class="small mt-2 d-none"></div>
                                        <small class="text-muted">Login credentials will be sent here</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_phone" class="form-label">Phone Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('guardian_phone') is-invalid @enderror"
                                            id="guardian_phone" name="guardian_phone"
                                            value="{{ old('guardian_phone') }}" required>
                                        @error('guardian_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="alert alert-info">
                            <i data-feather="info" class="icon-sm me-2"></i>
                            <strong>What happens next?</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <li>Student profile is created</li>
                                <li>Guardian account is created or reused</li>
                                <li>Login credentials sent only for new guardian accounts</li>
                                <li>Student is enrolled in <strong>{{ $section->name }}</strong></li>
                            </ul>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i data-feather="user-plus" class="icon-sm me-2"></i>
                                Enroll Student
                            </button>
                            <a href="{{ route('admin.sections.manage', $section) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            const form = document.getElementById('enrollStudentForm');
            const useExistingGuardianToggle = document.getElementById('use_existing_guardian');
            const existingGuardianSearchContainer = document.getElementById('existingGuardianSearchContainer');
            const existingGuardianSearchInput = document.getElementById('existing_guardian_search');
            const existingGuardianDropdown = document.getElementById('existing_guardian_dropdown');
            const guardianSearchStatus = document.getElementById('guardianSearchStatus');
            const guardianIdInput = document.getElementById('guardian_id');
            const guardianEmailInput = document.getElementById('guardian_email');
            const guardianFirstNameInput = document.getElementById('guardian_first_name');
            const guardianLastNameInput = document.getElementById('guardian_last_name');
            const guardianRelationshipInput = document.getElementById('guardian_relationship');
            const guardianPhoneInput = document.getElementById('guardian_phone');
            const guardianDetailsInputs = [
                guardianFirstNameInput,
                guardianLastNameInput,
                guardianRelationshipInput,
                guardianPhoneInput,
            ];
            const guardianSearchUrl = @json(route('admin.enrollment.guardian.search'));
            const guardiansById = new Map();
            let searchDebounceTimer = null;

            const setGuardianSearchStatus = (message, variant) => {
                if (!guardianSearchStatus) {
                    return;
                }

                guardianSearchStatus.classList.remove('d-none', 'text-success', 'text-danger', 'text-muted');
                if (variant === 'success') {
                    guardianSearchStatus.classList.add('text-success');
                } else if (variant === 'error') {
                    guardianSearchStatus.classList.add('text-danger');
                } else {
                    guardianSearchStatus.classList.add('text-muted');
                }
                guardianSearchStatus.textContent = message;
            };

            const clearGuardianSearchStatus = () => {
                if (!guardianSearchStatus) {
                    return;
                }

                guardianSearchStatus.classList.add('d-none');
                guardianSearchStatus.textContent = '';
            };

            const setGuardianDetailsDisabled = (disabled) => {
                guardianDetailsInputs.forEach((input) => {
                    if (input) {
                        input.disabled = disabled;
                    }
                });
            };

            const clearGuardianDetails = () => {
                if (guardianFirstNameInput) {
                    guardianFirstNameInput.value = '';
                }
                if (guardianLastNameInput) {
                    guardianLastNameInput.value = '';
                }
                if (guardianRelationshipInput) {
                    guardianRelationshipInput.value = '';
                }
                if (guardianPhoneInput) {
                    guardianPhoneInput.value = '';
                }
            };

            const resetGuardianSelection = () => {
                if (guardianIdInput) {
                    guardianIdInput.value = '';
                }
                if (guardianEmailInput) {
                    guardianEmailInput.value = '';
                }
                clearGuardianDetails();
                setGuardianDetailsDisabled(true);
            };

            const renderGuardianDropdown = (guardians) => {
                if (!existingGuardianDropdown) {
                    return;
                }

                guardiansById.clear();
                existingGuardianDropdown.innerHTML = '';

                if (!guardians || guardians.length === 0) {
                    existingGuardianDropdown.innerHTML =
                        '<div class="dropdown-item text-muted">No guardians found</div>';
                    existingGuardianDropdown.classList.add('show');
                    return;
                }

                guardians.forEach((guardian) => {
                    guardiansById.set(String(guardian.id), guardian);
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = `${guardian.full_name} - ${guardian.email}`;
                    item.dataset.id = String(guardian.id);
                    item.addEventListener('click', () => {
                        existingGuardianSearchInput.value = guardian.full_name || guardian.email || '';
                        fillGuardianDetails(guardian);
                        existingGuardianDropdown.classList.remove('show');
                    });
                    existingGuardianDropdown.appendChild(item);
                });

                existingGuardianDropdown.classList.add('show');
            };

            const fillGuardianDetails = (guardian) => {
                if (!guardian) {
                    return;
                }

                if (guardianIdInput) {
                    guardianIdInput.value = guardian.id || '';
                }
                if (guardianFirstNameInput) {
                    guardianFirstNameInput.value = guardian.first_name || '';
                }
                if (guardianLastNameInput) {
                    guardianLastNameInput.value = guardian.last_name || '';
                }
                if (guardianRelationshipInput) {
                    guardianRelationshipInput.value = guardian.relationship || '';
                }
                if (guardianPhoneInput) {
                    guardianPhoneInput.value = guardian.phone || '';
                }
                if (guardianEmailInput) {
                    guardianEmailInput.value = guardian.email || '';
                }

                setGuardianDetailsDisabled(false);

                const connectedStudent = guardian.connected_student;
                if (connectedStudent) {
                    const fullName = `${connectedStudent.first_name || ''} ${connectedStudent.last_name || ''}`.trim();
                    setGuardianSearchStatus(
                        `Guardian selected. Connected to student ${fullName}. Existing credentials will be used.`,
                        'success'
                    );
                } else {
                    setGuardianSearchStatus('Guardian selected. Existing credentials will be used.', 'success');
                }
            };

            const fetchGuardians = async () => {
                if (!useExistingGuardianToggle?.checked) {
                    return;
                }

                const searchText = existingGuardianSearchInput?.value?.trim() || '';
                if (searchText.length < 2) {
                    if (existingGuardianDropdown) {
                        existingGuardianDropdown.classList.remove('show');
                        existingGuardianDropdown.innerHTML = '';
                    }
                    guardiansById.clear();
                    resetGuardianSelection();
                    setGuardianSearchStatus('Type at least 2 characters to search.', 'muted');
                    return;
                }

                try {
                    const response = await fetch(`${guardianSearchUrl}?q=${encodeURIComponent(searchText)}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json();
                    const guardians = Array.isArray(payload.guardians) ? payload.guardians : [];

                    resetGuardianSelection();
                    renderGuardianDropdown(guardians);

                    if (guardians.length === 0) {
                        setGuardianSearchStatus('Guardian does not exist.', 'error');
                    } else {
                        setGuardianSearchStatus('Select a guardian from the dropdown.', 'muted');
                    }
                } catch (error) {
                    resetGuardianSelection();
                    if (existingGuardianDropdown) {
                        existingGuardianDropdown.classList.remove('show');
                        existingGuardianDropdown.innerHTML = '';
                    }
                    setGuardianSearchStatus('Unable to search guardians right now. Please try again.', 'error');
                }
            };

            const applyExistingGuardianMode = () => {
                const isExistingMode = !!useExistingGuardianToggle?.checked;

                if (isExistingMode) {
                    if (existingGuardianSearchContainer) {
                        existingGuardianSearchContainer.classList.remove('d-none');
                    }
                    if (guardianEmailInput) {
                        guardianEmailInput.disabled = true;
                    }
                    if (guardianIdInput?.value) {
                        setGuardianDetailsDisabled(false);
                        setGuardianSearchStatus('Existing guardian selected. Existing credentials will be used.', 'muted');
                    } else {
                        resetGuardianSelection();
                        setGuardianSearchStatus('Search and select an existing guardian.', 'muted');
                    }
                } else {
                    if (existingGuardianSearchContainer) {
                        existingGuardianSearchContainer.classList.add('d-none');
                    }
                    if (existingGuardianSearchInput) {
                        existingGuardianSearchInput.value = '';
                    }
                    if (existingGuardianDropdown) {
                        existingGuardianDropdown.classList.remove('show');
                        existingGuardianDropdown.innerHTML = '';
                    }
                    guardiansById.clear();
                    if (guardianIdInput) {
                        guardianIdInput.value = '';
                    }
                    if (guardianEmailInput) {
                        guardianEmailInput.disabled = false;
                    }
                    setGuardianDetailsDisabled(false);
                    clearGuardianSearchStatus();
                }
            };

            if (useExistingGuardianToggle) {
                useExistingGuardianToggle.addEventListener('change', function() {
                    applyExistingGuardianMode();
                });
            }

            if (existingGuardianSearchInput) {
                existingGuardianSearchInput.addEventListener('input', function() {
                    if (!useExistingGuardianToggle?.checked) {
                        return;
                    }

                    if (searchDebounceTimer) {
                        clearTimeout(searchDebounceTimer);
                    }

                    searchDebounceTimer = setTimeout(fetchGuardians, 250);
                });
                existingGuardianSearchInput.addEventListener('focus', function() {
                    if (!useExistingGuardianToggle?.checked) {
                        return;
                    }

                    if ((this.value || '').trim().length >= 2) {
                        fetchGuardians();
                    }
                });
            }

            document.addEventListener('click', function(e) {
                if (!existingGuardianSearchInput || !existingGuardianDropdown) {
                    return;
                }

                if (!existingGuardianSearchInput.contains(e.target) && !existingGuardianDropdown.contains(e.target)) {
                    existingGuardianDropdown.classList.remove('show');
                }
            });

            applyExistingGuardianMode();

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (useExistingGuardianToggle?.checked && !guardianIdInput?.value) {
                        e.preventDefault();
                        setGuardianSearchStatus('Search and select an existing guardian first.', 'error');
                        return;
                    }

                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Enrolling...';
                });
            }
        });
    </script>
@endpush
