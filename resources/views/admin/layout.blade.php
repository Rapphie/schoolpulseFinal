<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SchoolPulse')</title>
    @yield('head')
    @include('components.header')

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
        @yield('content')
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
