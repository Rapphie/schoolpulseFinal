@extends('base')

@section('title', 'My Profile')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">My Profile</h1>
        </div>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i data-feather="user" class="icon-sm me-2"></i>Profile Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row mb-4">
                                <div class="col-md-3 text-center">
                                    @php
                                        $profilePath = $user->profile_picture;
                                        if ($profilePath && preg_match('/^https?:\/\//i', $profilePath)) {
                                            $profileUrl = $profilePath;
                                        } elseif ($profilePath) {
                                            $normalized = ltrim($profilePath, '/');
                                            $profileUrl = str_starts_with($normalized, 'storage/')
                                                ? asset($normalized)
                                                : asset('storage/' . $normalized);
                                        } else {
                                            $profileUrl = asset('images/user-placeholder.png');
                                        }
                                    @endphp
                                    <img id="profile_picture_preview" src="{{ $profileUrl }}"
                                        data-original-src="{{ $profileUrl }}" class="rounded-circle mb-3"
                                        style="width: 120px; height: 120px; object-fit: cover;" alt="Profile Picture">
                                    <div>
                                        <label for="profile_picture" class="btn btn-outline-primary btn-sm">
                                            <i data-feather="camera" class="icon-sm me-1"></i>Change Photo
                                        </label>
                                        <input type="file" class="d-none @error('profile_picture') is-invalid @enderror"
                                            id="profile_picture" name="profile_picture" accept="image/*">
                                        @error('profile_picture')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <button type="button" id="profile_picture_cancel"
                                            class="btn btn-outline-secondary btn-sm mt-2" style="display: none;">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">First Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('first_name') is-invalid @enderror"
                                                id="first_name" name="first_name"
                                                value="{{ old('first_name', $user->first_name) }}" required>
                                            @error('first_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">Last Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                                                name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                            @error('last_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="{{ $user->role->name ?? 'N/A' }}"
                                            disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i data-feather="save" class="icon-sm me-1"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i data-feather="lock" class="icon-sm me-2"></i>Change Password
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label">Current Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            id="current_password" name="current_password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password"
                                            data-target="current_password" aria-label="Toggle current password visibility"
                                            title="Show password">
                                            <i class="fas fa-eye icon-sm"></i>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="password" class="form-label">New Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control @error('password') is-invalid @enderror" id="password"
                                            name="password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password"
                                            data-target="password" aria-label="Toggle new password visibility"
                                            title="Show password">
                                            <i class="fas fa-eye icon-sm"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm New Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password_confirmation"
                                            name="password_confirmation" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password"
                                            data-target="password_confirmation"
                                            aria-label="Toggle confirm password visibility" title="Show password">
                                            <i class="fas fa-eye icon-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning">
                                    <i data-feather="key" class="icon-sm me-1"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i data-feather="info" class="icon-sm me-2"></i>Account Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <strong>Full Name:</strong>
                                <span class="d-block text-muted">{{ $user->full_name }}</span>
                            </li>
                            <li class="mb-3">
                                <strong>Email:</strong>
                                <span class="d-block text-muted">{{ $user->email }}</span>
                            </li>
                            <li class="mb-3">
                                <strong>Role:</strong>
                                <span class="badge bg-primary">{{ ucfirst($user->role->name ?? 'User') }}</span>
                            </li>
                            <li class="mb-3">
                                <strong>Account Created:</strong>
                                <span class="d-block text-muted">{{ $user->created_at->format('F d, Y') }}</span>
                            </li>
                            <li>
                                <strong>Last Updated:</strong>
                                <span class="d-block text-muted">{{ $user->updated_at->format('F d, Y h:i A') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('profile_picture');
            const preview = document.getElementById('profile_picture_preview');
            const cancelBtn = document.getElementById('profile_picture_cancel');

            if (!input || !preview || !cancelBtn) return;

            let objectUrl = null;

            function resetToOriginal() {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
                input.value = '';
                preview.src = preview.getAttribute('data-original-src') || preview.src;
                cancelBtn.style.display = 'none';
            }

            input.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                    }
                    objectUrl = URL.createObjectURL(e.target.files[0]);
                    preview.src = objectUrl;
                    cancelBtn.style.display = 'inline-block';
                }
            });

            cancelBtn.addEventListener('click', resetToOriginal);

            // Password visibility toggles
            document.querySelectorAll('.toggle-password').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = this.getAttribute('data-target');
                    var input = document.getElementById(targetId);
                    if (!input) return;
                    var isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';

                    var icon = this.querySelector('i');
                    if (icon) {
                        if (isPassword) {
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
                    this.title = isPassword ? 'Hide password' : 'Show password';
                });
            });
        });
    </script>
@endpush
