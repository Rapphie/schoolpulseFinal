<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SchoolPulse Change Password</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    @include('components.head')
    <link rel="stylesheet" href="{{ asset('css/auth/change-password.css') }}">
</head>

<body>
    <div class="login-card row g-0">
        <div class="col-md-5 login-image">
            <img src="{{ asset('images/school-logo.png') }}" alt="SchoolPulse Logo">
        </div>
        <div class="col-md-7">
            <div class="login-content">
                <h3>Change Password</h3>
                <form method="post" action="{{ route('password.update') }}" autocomplete="off">
                    @csrf
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="mb-4">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input name="current_password" type="password" class="form-control" id="current_password"
                                placeholder="Enter your current password" required>
                            <button class="input-group-text bg-white" type="button" id="toggleCurrentPassword">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input name="password" type="password" class="form-control" id="password"
                                placeholder="Enter your new password" required>
                            <button class="input-group-text bg-white" type="button" id="togglePassword">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input name="password_confirmation" type="password" class="form-control"
                                id="password_confirmation" placeholder="Confirm your new password" required>
                            <button class="input-group-text bg-white" type="button" id="togglePasswordConfirmation">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function togglePasswordVisibility(buttonId, inputId) {
                const toggleButton = document.getElementById(buttonId);
                const passwordInput = document.getElementById(inputId);

                if (toggleButton && passwordInput) {
                    toggleButton.addEventListener('click', function() {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' :
                            'password';
                        passwordInput.setAttribute('type', type);
                        this.querySelector('i').classList.toggle('fa-eye');
                        this.querySelector('i').classList.toggle('fa-eye-slash');
                    });
                }
            }

            togglePasswordVisibility('toggleCurrentPassword', 'current_password');
            togglePasswordVisibility('togglePassword', 'password');
            togglePasswordVisibility('togglePasswordConfirmation', 'password_confirmation');
        });
    </script>

</body>

</html>
