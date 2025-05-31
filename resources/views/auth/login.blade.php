<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SchoolPulse login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">

    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --text-color: #2b2d42;
            --light-gray: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-color);
        }

        .login-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 1000px;
            width: 90%;
            margin: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(67, 97, 238, 0.1);
        }

        .login-content {
            padding: 3.5rem 2.5rem;
            position: relative;
        }

        .login-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .login-content h3 {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-size: 1.75rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .login-content h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .btn-primary {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.75rem 2rem;
            background-color: var(--primary-color);
            border: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .login-image {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #4361ee 60%, #3a0ca3 100%);
            position: relative;
            overflow: hidden;
        }

        .login-image::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 60%);
            transform: rotate(30deg);
        }

        .login-image img {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
            position: relative;
            z-index: 1;
            transition: transform 0.5s ease;
        }

        .login-card:hover .login-image img {
            transform: scale(1.05) rotate(-2deg);
        }

        @media (max-width: 768px) {
            .login-card {
                flex-direction: column;
                margin: 1rem;
                width: 95%;
            }

            .login-content {
                padding: 2rem 1.5rem;
            }

            .login-content::before {
                width: 100%;
                height: 4px;
                top: 0;
                left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="login-card row g-0">
        <div class="col-md-5 login-image">
            <img src="images/school-logo.png" alt="SchoolPulse Logo">
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
                            <option value="3">Parent</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="text-decoration-none small text-muted">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Sign In
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

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>

</body>

</html>
