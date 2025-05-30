<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    @stack('head')
    <title>@yield('title', 'SchoolPulse - Teacher Portal')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Custom CSS -->
    {{-- <link href="{{ asset('css/styles.css') }}" rel="stylesheet"> --}}

    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: white;
            border-right: 1px solid #dee2e6;
            padding: 1rem;
            transition: all 0.3s ease;
            z-index: 1040;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 1rem 2rem;
            transition: all 0.3s ease;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-link:hover {
            background-color: #f8f9fa;
        }

        .nav-link.active {
            background-color: #4e73df;
            color: white !important;
        }

        .dropdown-menu {
            border-radius: 0.35rem;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .dropdown-item {
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }

        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-title {
            margin-bottom: 0;
            color: #5a5c69;
            font-weight: 700;
        }

        .table {
            color: #5a5c69;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 700;
        }

        .brand-logo {
            height: 40px;
        }

        .mini-logo-container {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-circle {
            border-radius: 100%;
            height: 2.5rem;
            width: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text-primary {
            color: #4e73df !important;
        }

        .bg-primary {
            background-color: #4e73df !important;
        }

        .bg-success {
            background-color: #1cc88a !important;
        }

        .bg-info {
            background-color: #36b9cc !important;
        }

        .bg-warning {
            background-color: #f6c23e !important;
        }

        .bg-danger {
            background-color: #e74a3b !important;
        }

        .bg-secondary {
            background-color: #858796 !important;
        }
    </style>

    @stack('styles')
</head>

<body>
    <!-- Sidebar -->
    @include('components.sidebar')

    <!-- Main Content Area -->
    <div class="content-wrapper">
        <div class="container-fluid px-4">
            <h1 class="mt-4 mb-4">@yield('title', 'Teacher Dashboard')</h1>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Init feather icons
            feather.replace();
        });
    </script>

    @stack('scripts')
</body>

</html>
