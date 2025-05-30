<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SchoolPulse')</title>

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

        /* Sidebar collapsed state - mini sidebar for larger screens */
        .sidebar.collapsed {
            width: 70px;
            padding: 1rem 0.5rem;
            overflow: hidden;
        }

        /* Hide text in mini sidebar */
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .brand span,
        .sidebar.collapsed hr {
            display: none;
        }

        /* Center icons in mini sidebar */
        .sidebar.collapsed .nav-link {
            display: flex;
            justify-content: center;
            padding: 0.5rem;
        }

        /* Center brand logo in mini sidebar */
        .sidebar.collapsed .brand {
            justify-content: center !important;
            margin-bottom: 1rem !important;
            padding: 0;
            width: 100%;
        }

        /* Special styling for logo in mini sidebar */
        .sidebar.collapsed .brand img {
            margin: 0 auto !important;
            padding: 0 !important;
            display: block;
        }

        /* Content adjustment when sidebar is collapsed */
        .content-shifted {
            margin-left: 70px !important;
            width: calc(100% - 70px) !important;
        }

        /* Mobile-specific styles */
        @media (max-width: 767.98px) {
            .sidebar.collapsed {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            /* Show all text elements even in collapsed+show state on mobile */
            .sidebar.collapsed.show .nav-link span,
            .sidebar.collapsed.show .brand span,
            .sidebar.collapsed.show hr {
                display: block;
            }

            /* Reset nav links on mobile */
            .sidebar.collapsed.show .nav-link {
                display: flex;
                justify-content: flex-start;
                padding: 0.5rem 1rem;
            }

            /* Reset brand alignment on mobile */
            .sidebar.collapsed.show .brand {
                justify-content: flex-start !important;
            }

            .top-bar {
                padding-left: 1rem !important;
            }

            main {
                margin-left: 0 !important;
            }

            /* Ensure hamburger is properly positioned on mobile */
            .hamburger {
                position: fixed;
                left: 10px;
                z-index: 1070;
            }

            /* Move hamburger when sidebar is open */
            .sidebar.show~#content .hamburger {
                left: calc(100% - 50px);
                transition: left 0.3s ease;
            }

            /* Always fully collapse on mobile */
            .content-shifted {
                margin-left: 0 !important;
            }
        }

        .top-bar {
            height: 60px;
            background-color: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 1rem;
            padding-left: 270px;
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: padding-left 0.3s ease;
        }

        .hamburger {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            margin-right: 1rem;
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .hamburger:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Responsive topbar styles */
        .school-title {
            font-size: 1.575rem;
            transition: font-size 0.3s ease;
        }

        /* Medium screens */
        @media (max-width: 1200px) {
            .school-title {
                font-size: 1.25rem;
            }
        }

        /* Small screens */
        @media (max-width: 820px) {

            /*fully hide sidebar when collapsed */
            .sidebar {
                transform: translateX(-100%);
                width: 100% !important;
                /* Full width on mobile */
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1060;
            }

            .school-title {
                display: none;
            }

            .time-container {
                display: none;
            }

            .top-bar {
                justify-content: space-between;
                padding-left: 1rem !important;
            }
        }

        /* Extra small screens */
        @media (max-width: 576px) {}

        main {
            margin-left: 270px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .card {
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .brand img {
            height: 40px;
            width: 40px;
        }

        /* Special styling for mini sidebar logo */
        .sidebar.collapsed .brand {
            justify-content: center !important;
            margin-bottom: 1.5rem !important;
            display: flex;
            align-items: center;
        }

        .sidebar.collapsed .brand-logo {
            margin: 0 !important;
            padding: 0 !important;
            display: block;
        }

        .bg-soft-primary {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-soft-success {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-soft-warning {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-soft-info {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .feather-sm {
            width: 18px;
            height: 18px;
        }

        /* Specific container for mini sidebar logo */
        .mini-logo-container {
            display: flex;
            align-items: center;
        }

        .sidebar.collapsed .mini-logo-container {
            display: flex;
            justify-content: center;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .sidebar.collapsed .mini-logo-container img {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* User dropdown styling */
        .dropdown-toggle::after {
            margin-left: 0.5rem;
        }

        .dropdown-menu {
            min-width: 12rem;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
        }

        /* Sidebar submenu styling */
        .sidebar .collapse {
            transition: all 0.3s ease;
        }

        .sidebar .collapse ul li a {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .sidebar .collapse ul li a i {
            width: 16px;
            height: 16px;
            margin-right: 5px;
        }

        .sidebar.collapsed .collapse {
            display: none;
        }

        .sidebar .nav-link[aria-expanded="true"] {
            color: #0d6efd;
        }

        /* Make sure submenu styling works in mini sidebar mode */
        .sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }
    </style>

    @stack('styles')
</head>

<body class="bg-light">
    @include('components.sidebar')

    <div id="content" class="w-100">
        @include('components.topbar')

        <!-- Main Content -->
        <main class="p-4">
            <!-- Header with Welcome Message -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 mb-1 fw-bold text-dark">Welcome back, {{ Auth::user()->last_name }}</h2>
                    <p class="text-muted mb-0">Here's what's happening with your school today</p>
                </div>
                <div>
                    <button class="btn btn-primary">
                        <i data-feather="plus" class="feather-sm me-1"></i> Add New
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-soft-primary rounded p-2">
                                    <i data-feather="users" class="text-primary"></i>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success">+12%</span>
                            </div>
                            <h3 class="mb-1">355</h3>
                            <p class="text-muted mb-0">Enrolled Students</p>
                            <div class="progress mt-3" style="height: 4px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 75%"
                                    aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-soft-success rounded p-2">
                                    <i data-feather="user-check" class="text-success"></i>
                                </div>
                                <span class="badge bg-danger bg-opacity-10 text-danger">-2%</span>
                            </div>
                            <h3 class="mb-1">42</h3>
                            <p class="text-muted mb-0">Teaching Staff</p>
                            <div class="progress mt-3" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 65%"
                                    aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-soft-warning rounded p-2">
                                    <i data-feather="book-open" class="text-warning"></i>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success">+5%</span>
                            </div>
                            <h3 class="mb-1">18</h3>
                            <p class="text-muted mb-0">Active Classes</p>
                            <div class="progress mt-3" style="height: 4px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 85%"
                                    aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-soft-info rounded p-2">
                                    <i data-feather="check-circle" class="text-info"></i>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success">+8%</span>
                            </div>
                            <h3 class="mb-1">94%</h3>
                            <p class="text-muted mb-0">Attendance Today</p>
                            <div class="progress mt-3" style="height: 4px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 94%"
                                    aria-valuenow="94" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 fw-bold">Student Enrollment Overview</h5>
                        </div>
                        <div class="card-body">
                            <div id="enrollmentChart" style="height: 300px;">
                                <!-- Chart will be rendered here by JavaScript -->
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 fw-bold">Class Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div id="classDistributionChart" style="height: 300px;">
                                <!-- Chart will be rendered here by JavaScript -->
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Upcoming Events -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 fw-bold">Recent Activity</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="avatar avatar-sm bg-soft-primary text-primary rounded-circle p-2 me-3">
                                                <i data-feather="user-plus" class="feather-sm"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1 fw-semibold">New Student Enrolled</h6>
                                                <small class="text-muted">2m ago</small>
                                            </div>
                                            <p class="mb-0 text-muted">John Doe has been enrolled in Grade 5-A</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="avatar avatar-sm bg-soft-success text-success rounded-circle p-2 me-3">
                                                <i data-feather="check-circle" class="feather-sm"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1 fw-semibold">Attendance Marked</h6>
                                                <small class="text-muted">1h ago</small>
                                            </div>
                                            <p class="mb-0 text-muted">Attendance marked for Class 10-A (95% present)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="avatar avatar-sm bg-soft-warning text-warning rounded-circle p-2 me-3">
                                                <i data-feather="alert-triangle" class="feather-sm"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1 fw-semibold">Low Attendance Alert</h6>
                                                <small class="text-muted">3h ago</small>
                                            </div>
                                            <p class="mb-0 text-muted">Attendance below 80% for Class 8-B</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Upcoming Events</h5>
                            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 text-center me-3">
                                            <div class="bg-soft-primary text-primary p-2 rounded-3"
                                                style="width: 50px;">
                                                <div class="fw-bold">25</div>
                                                <small class="d-block">MAY</small>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-semibold">Annual Sports Day</h6>
                                            <p class="mb-0 text-muted small">9:00 AM - 2:00 PM</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 text-center me-3">
                                            <div class="bg-soft-success text-success p-2 rounded-3"
                                                style="width: 50px;">
                                                <div class="fw-bold">30</div>
                                                <small class="d-block">MAY</small>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-semibold">Parent-Teacher Meeting</h6>
                                            <p class="mb-0 text-muted small">10:00 AM - 4:00 PM</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 text-center me-3">
                                            <div class="bg-soft-warning text-warning p-2 rounded-3"
                                                style="width: 50px;">
                                                <div class="fw-bold">05</div>
                                                <small class="d-block">JUN</small>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-semibold">First Term Exams Begin</h6>
                                            <p class="mb-0 text-muted small">All Day</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    {{-- <script src="{{ asset('js/scripts.js') }}"></script> --}}

    <script>
        // Function to update Philippine time and date
        function updatePhilippineTime() {
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const dateOptions = {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-PH', options);
            const dateString = now.toLocaleDateString('en-PH', dateOptions);
            const timeElement = document.getElementById('ph-time');
            if (timeElement) {
                timeElement.innerHTML = `
          <div class="text-end">${timeString}</div>
          <div class="small">${dateString} (PHT)</div>
        `;
            }
        }

        // Update time immediately and then every second
        updatePhilippineTime();
        setInterval(updatePhilippineTime, 1000);
    </script>

    <script>
        // Initialize Feather Icons
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all feather icons
            feather.replace();

            // Toggle sidebar on all screen sizes
            const toggleBtn = document.getElementById('toggleBtn');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const mainContent = document.querySelector('main');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    sidebar.classList.toggle('show');
                    content.classList.toggle('content-shifted');

                    // Adjust top-bar padding when sidebar is collapsed
                    const topBar = document.querySelector('.top-bar');
                    if (topBar) {
                        if (window.innerWidth <= 767.98) {
                            // On mobile
                            if (sidebar.classList.contains('show')) {
                                topBar.style.paddingLeft = '270px';
                            } else {
                                topBar.style.paddingLeft = '1rem';
                            }
                        } else {
                            // On desktop
                            if (sidebar.classList.contains('collapsed')) {
                                topBar.style.paddingLeft = '90px'; // Mini sidebar width + padding
                            } else {
                                topBar.style.paddingLeft = '270px';
                            }
                        }
                    }

                    // Adjust main content margin
                    if (mainContent) {
                        if (window.innerWidth <= 767.98) {
                            // On mobile
                            mainContent.style.marginLeft = '0';
                        } else {
                            // On desktop
                            if (sidebar.classList.contains('collapsed')) {
                                mainContent.style.marginLeft = '70px'; // Mini sidebar width
                            } else {
                                mainContent.style.marginLeft = '250px';
                            }
                        }
                    }

                    // Update hamburger position on small screens
                    if (window.innerWidth <= 767.98) {
                        if (sidebar.classList.contains('show')) {
                            toggleBtn.style.left = 'calc(100% - 50px)';
                        } else {
                            toggleBtn.style.left = '10px';
                        }

                        // Add body overflow hidden to prevent scrolling when sidebar is open
                        if (sidebar.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    }
                });
            }

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (sidebar && toggleBtn) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggleBtn = toggleBtn.contains(event.target);

                    if (!isClickInsideSidebar && !isClickOnToggleBtn) {
                        if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                            sidebar.classList.remove('show');
                        }
                    }
                }
            });

            // Add active class to current nav item
            const currentPage = window.location.pathname.split('/').pop() || 'dashboard';
            const navLinks = document.querySelectorAll('.nav-link');

            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href.includes(currentPage)) {
                    link.classList.add('active');
                } else if (currentPage === '' && href && href.includes('dashboard')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Initialize hamburger position on page load
            if (window.innerWidth <= 767.98 && toggleBtn) {
                if (sidebar && sidebar.classList.contains('show')) {
                    toggleBtn.style.left = 'calc(100% - 50px)';
                    document.body.style.overflow = 'hidden';
                } else {
                    toggleBtn.style.left = '10px';
                    document.body.style.overflow = '';
                }
            }

            // Re-initialize Feather icons when dropdowns are shown
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('shown.bs.dropdown', function() {
                    feather.replace();
                });
            });

            // Initialize sidebar submenu icons
            document.querySelectorAll('.sidebar .collapse').forEach(submenu => {
                submenu.addEventListener('shown.bs.collapse', function() {
                    feather.replace();
                });
            });

            // Auto-open active submenu on page load
            document.querySelectorAll('.sidebar .collapse.show').forEach(menu => {
                const toggler = document.querySelector(`[href="#${menu.id}"]`);
                if (toggler) {
                    toggler.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('main');
            const topBar = document.querySelector('.top-bar');
            const toggleBtn = document.getElementById('toggleBtn');

            if (sidebar) {
                if (window.innerWidth >= 992) {
                    // For larger screens, reset to default view if sidebar was hidden on mobile
                    if (sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }

                    // Keep collapsed state persistent across screen sizes
                    if (!sidebar.classList.contains('collapsed')) {
                        if (mainContent) mainContent.style.marginLeft = '250px';
                        if (topBar) topBar.style.paddingLeft = '270px';
                    } else {
                        // Mini sidebar mode
                        if (mainContent) mainContent.style.marginLeft = '70px';
                        if (topBar) topBar.style.paddingLeft = '90px';
                    }

                    if (toggleBtn) toggleBtn.style.left = 'auto';
                } else {
                    if (mainContent) mainContent.style.marginLeft = '0';
                    if (topBar) topBar.style.paddingLeft = '1rem';

                    // Update hamburger position
                    if (toggleBtn) {
                        if (sidebar.classList.contains('show')) {
                            toggleBtn.style.left = 'calc(100% - 50px)';
                            document.body.style.overflow = 'hidden';
                        } else {
                            toggleBtn.style.left = '10px';
                            document.body.style.overflow = '';
                        }
                    }
                }
            }
        });
    </script>

    @stack('scripts')

    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Enrollment Chart
            const enrollmentData = {
                months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                series: [{
                    name: 'Students',
                    data: [320, 332, 345, 340, 355, 365, 360, 370, 385, 390, 380, 375]
                }]
            };

            const enrollmentOptions = {
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#0d6efd'],
                series: enrollmentData.series,
                xaxis: {
                    categories: enrollmentData.months
                },
                yaxis: {
                    title: {
                        text: 'Number of Students'
                    },
                    min: 300
                },
                markers: {
                    size: 4
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(value) {
                            return value + ' students';
                        }
                    }
                },
                title: {
                    text: 'Monthly Enrollment (2025)',
                    align: 'left',
                    style: {
                        fontSize: '14px',
                        fontWeight: 'normal',
                        color: '#555'
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    row: {
                        colors: ['#f5f5f5', 'transparent'],
                        opacity: 0.5
                    }
                }
            };

            // Initialize Class Distribution Chart
            const classData = {
                series: [35, 25, 20, 15, 5],
                labels: ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5']
            };

            const classOptions = {
                chart: {
                    type: 'donut',
                    height: 300
                },
                colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1'],
                series: classData.series,
                labels: classData.labels,
                legend: {
                    position: 'bottom'
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }],
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + '% of students';
                        }
                    }
                }
            };

            // Remove loading spinners and render charts
            const enrollmentChartElement = document.getElementById('enrollmentChart');
            if (enrollmentChartElement) {
                enrollmentChartElement.innerHTML = '';
                const enrollmentChart = new ApexCharts(enrollmentChartElement, enrollmentOptions);
                enrollmentChart.render();
            }

            const classDistributionElement = document.getElementById('classDistributionChart');
            if (classDistributionElement) {
                classDistributionElement.innerHTML = '';
                const classDistributionChart = new ApexCharts(classDistributionElement, classOptions);
                classDistributionChart.render();
            }
        });
    </script>
</body>

</html>
