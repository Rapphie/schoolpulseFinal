<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SchoolPulse login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.head')
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
</head>

<body>
    <div class="login-card row g-0">
        <div class="col-md-5 login-image">
            <img src="{{ asset('images/school-logo.png') }}" alt="SchoolPulse Logo">
        </div>
        <div class="col-md-7">
            <div class="login-content">
                <h3>Welcome to SchoolPulse</h3>
                <form method="post" action="{{ route('authenticate') }}" id="form" autocomplete="off">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-envelope text-muted"></i>
                            </span>
                            <input name="email" type="text" class="form-control" id="email"
                                placeholder="Enter your email" required>
                        </div>
                        <div class="form-text">We'll never share your email.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input name="password" type="password" class="form-control" id="password"
                                placeholder="Enter your password" required>
                            <button class="input-group-text bg-white" type="button" id="togglePassword">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Login As</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">-- Select user type -- </option>
                            <option value="1">Administrator</option>
                            <option value="2">Teacher</option>
                            <option value="3">Guardian</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="#" id="forgot-password-link" class="text-primary small">Forgot
                            password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" id="login-button">
                        Sign In
                    </button>
                    <button type="button" class="btn btn-primary w-100 py-2 mt-2 d-none" id="recover-button">
                        Recover Account
                    </button>
                    <div class="mt-3">
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div class=" text-danger">
                                    <i class="fa fa-fw fa-danger"></i>
                                    <strong>Error!</strong> {{ $error }}
                                </div>
                            @endforeach
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const loginButton = document.getElementById('login-button');
            const forgotPasswordLink = document.getElementById('forgot-password-link');
            const recoverButton = document.getElementById('recover-button');
            const passwordField = document.querySelector('label[for="password"]').parentElement;
            const roleField = document.querySelector('label[for="role"]').parentElement;
            const rememberMeField = document.querySelector('.form-check');
            const form = document.getElementById('form');
            const h3 = document.querySelector('.login-content h3');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

            forgotPasswordLink.addEventListener('click', function(e) {
                e.preventDefault();
                passwordField.classList.add('d-none');
                roleField.classList.add('d-none');
                rememberMeField.classList.add('d-none');
                loginButton.classList.add('d-none');
                recoverButton.classList.remove('d-none');
                h3.textContent = 'Recover Account';
                form.action = "{{ route('password.email') }}";
                forgotPasswordLink.textContent = 'Back to Login';
            });

            recoverButton.addEventListener('click', function() {
                form.submit();
            });
        });
    </script>

</body>

</html>
