@extends('base')

@section('title', 'Edit Student')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Classes</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.students.show', $student) }}">Student Profile</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Student</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Edit Student: {{ $student->first_name }}
                            {{ $student->last_name }}</h6>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('admin.students.update', $student) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <h6 class="mb-3 border-bottom pb-2">Student Information</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="lrn" class="form-label">LRN</label>
                                    <input type="text" class="form-control" id="lrn" name="lrn"
                                        value="{{ old('lrn', $student->lrn) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="first_name" class="form-label">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="{{ old('first_name', $student->first_name) }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="{{ old('last_name', $student->last_name) }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="male"
                                            {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Male
                                        </option>
                                        <option value="female"
                                            {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="birthdate" class="form-label">Birthdate <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="birthdate" name="birthdate"
                                        value="{{ old('birthdate', $student->birthdate?->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $student->address) }}</textarea>
                            </div>

                            <h6 class="mt-4 mb-3 border-bottom pb-2">Additional Information (For Analytics)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="distance_km" class="form-label">Distance from School (km)</label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        id="distance_km" name="distance_km"
                                        value="{{ old('distance_km', $student->distance_km) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="transportation" class="form-label">Mode of Transportation</label>
                                    <select class="form-select" id="transportation" name="transportation">
                                        @php
                                            $transport = old('transportation', $student->transportation);
                                        @endphp
                                        <option value="" {{ $transport == '' ? 'selected' : '' }}>-- Select --
                                        </option>
                                        <option value="Walk" {{ $transport == 'Walk' ? 'selected' : '' }}>Walk</option>
                                        <option value="Bicycle" {{ $transport == 'Bicycle' ? 'selected' : '' }}>Bicycle
                                        </option>
                                        <option value="Motorcycle" {{ $transport == 'Motorcycle' ? 'selected' : '' }}>
                                            Motorcycle</option>
                                        <option value="Tricycle" {{ $transport == 'Tricycle' ? 'selected' : '' }}>Tricycle
                                        </option>
                                        <option value="Jeepney" {{ $transport == 'Jeepney' ? 'selected' : '' }}>Jeepney
                                        </option>
                                        <option value="Bus" {{ $transport == 'Bus' ? 'selected' : '' }}>Bus</option>
                                        <option value="Private Vehicle"
                                            {{ $transport == 'Private Vehicle' ? 'selected' : '' }}>Private Vehicle
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="family_income" class="form-label">Socioeconomic Status</label>
                                    <select class="form-select" id="family_income" name="family_income">
                                        @php
                                            $income = old('family_income', $student->family_income);
                                        @endphp
                                        <option value="" {{ $income == '' ? 'selected' : '' }}>-- Select --</option>
                                        <option value="Low" {{ $income == 'Low' ? 'selected' : '' }}>Low</option>
                                        <option value="Medium" {{ $income == 'Medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="High" {{ $income == 'High' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="mt-4 mb-3 border-bottom pb-2">Guardian Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_first_name" class="form-label">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guardian_first_name"
                                        name="guardian_first_name"
                                        value="{{ old('guardian_first_name', $student->guardian->user->first_name ?? '') }}"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_last_name" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guardian_last_name"
                                        name="guardian_last_name"
                                        value="{{ old('guardian_last_name', $student->guardian->user->last_name ?? '') }}"
                                        required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="guardian_email" name="guardian_email"
                                        value="{{ old('guardian_email', $student->guardian->user->email ?? '') }}"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_phone" class="form-label">Phone <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guardian_phone" name="guardian_phone"
                                        value="{{ old('guardian_phone', $student->guardian->phone ?? '') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="guardian_relationship" class="form-label">Relationship to Student <span
                                        class="text-danger">*</span></label>
                                @php
                                    $rel = old('guardian_relationship', $student->guardian->relationship ?? 'parent');
                                @endphp
                                <select class="form-select" id="guardian_relationship" name="guardian_relationship"
                                    required>
                                    <option value="parent" {{ $rel == 'parent' ? 'selected' : '' }}>Parent</option>
                                    <option value="sibling" {{ $rel == 'sibling' ? 'selected' : '' }}>Sibling</option>
                                    <option value="relative" {{ $rel == 'relative' ? 'selected' : '' }}>Relative</option>
                                    <option value="guardian" {{ $rel == 'guardian' ? 'selected' : '' }}>Guardian</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('admin.students.show', $student) }}"
                                    class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
