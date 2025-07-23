<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SchoolPulse')</title>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    @yield('head')
    @include('components.head')

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
            // Academic year data with different enrollment numbers
            const academicYearEnrollmentData = {
                'current': [320, 332, 345, 340, 355, 365, 360, 370, 385, 390, 380, 375],
                '2024-2025': [290, 305, 318, 325, 340, 350, 345, 360, 370, 375, 365, 355],
                '2023-2024': [265, 280, 295, 305, 315, 330, 325, 335, 345, 350, 340, 330]
            };

            // Enhanced school year data structure with more comprehensive information
            const academicYearsData = [{
                    id: 1,
                    name: '2025-2026',
                    startDate: '2025-06-15',
                    endDate: '2026-03-31',
                    isCurrent: true,
                    description: 'Regular school year with enhanced curriculum focus',
                    enrollmentData: academicYearEnrollmentData.current,
                    terms: [{
                            name: 'First Term',
                            start: '2025-06-15',
                            end: '2025-10-25'
                        },
                        {
                            name: 'Second Term',
                            start: '2025-11-03',
                            end: '2026-03-31'
                        }
                    ],
                    statistics: {
                        totalStudents: 375,
                        newEnrollees: 65,
                        graduatingStudents: 48,
                        attendance: 94
                    },
                    events: [{
                            name: 'School Opening',
                            date: '2025-06-15'
                        },
                        {
                            name: 'First Term Exams',
                            date: '2025-10-20'
                        },
                        {
                            name: 'Christmas Break',
                            date: '2025-12-20'
                        },
                        {
                            name: 'School Resumes',
                            date: '2026-01-06'
                        },
                        {
                            name: 'Final Exams',
                            date: '2026-03-25'
                        },
                        {
                            name: 'Graduation',
                            date: '2026-03-31'
                        }
                    ]
                },
                {
                    id: 2,
                    name: '2024-2025',
                    startDate: '2024-06-10',
                    endDate: '2025-03-25',
                    isCurrent: false,
                    description: 'Academic year with focus on technology integration',
                    enrollmentData: academicYearEnrollmentData['2024-2025'],
                    terms: [{
                            name: 'First Term',
                            start: '2024-06-10',
                            end: '2024-10-20'
                        },
                        {
                            name: 'Second Term',
                            start: '2024-11-04',
                            end: '2025-03-25'
                        }
                    ],
                    statistics: {
                        totalStudents: 355,
                        newEnrollees: 55,
                        graduatingStudents: 45,
                        attendance: 92
                    },
                    events: [{
                            name: 'School Opening',
                            date: '2024-06-10'
                        },
                        {
                            name: 'First Term Exams',
                            date: '2024-10-15'
                        },
                        {
                            name: 'Christmas Break',
                            date: '2024-12-20'
                        },
                        {
                            name: 'School Resumes',
                            date: '2025-01-06'
                        },
                        {
                            name: 'Final Exams',
                            date: '2025-03-20'
                        },
                        {
                            name: 'Graduation',
                            date: '2025-03-25'
                        }
                    ]
                },
                {
                    id: 3,
                    name: '2023-2024',
                    startDate: '2023-06-12',
                    endDate: '2024-03-28',
                    isCurrent: false,
                    description: 'Academic year with focus on holistic development',
                    enrollmentData: academicYearEnrollmentData['2023-2024'],
                    terms: [{
                            name: 'First Term',
                            start: '2023-06-12',
                            end: '2023-10-25'
                        },
                        {
                            name: 'Second Term',
                            start: '2023-11-06',
                            end: '2024-03-28'
                        }
                    ],
                    statistics: {
                        totalStudents: 330,
                        newEnrollees: 52,
                        graduatingStudents: 42,
                        attendance: 90
                    },
                    events: [{
                            name: 'School Opening',
                            date: '2023-06-12'
                        },
                        {
                            name: 'First Term Exams',
                            date: '2023-10-20'
                        },
                        {
                            name: 'Christmas Break',
                            date: '2023-12-20'
                        },
                        {
                            name: 'School Resumes',
                            date: '2024-01-08'
                        },
                        {
                            name: 'Final Exams',
                            date: '2024-03-22'
                        },
                        {
                            name: 'Graduation',
                            date: '2024-03-28'
                        }
                    ]
                }
            ];

            // Function to generate random enrollment data for years not in our predefined data
            function generateRandomEnrollmentData(baseYear) {
                // Use base year data if available, otherwise use current year
                const baseData = academicYearEnrollmentData[baseYear] || academicYearEnrollmentData.current;

                // Generate new random data based on the base data
                // Values will be ±15% of the base data
                return baseData.map(val => {
                    const variation = Math.random() * 0.3 - 0.15; // Random between -15% and +15%
                    return Math.round(val * (1 + variation));
                });
            }

            // Function to generate a complete school year data object
            function generateNewAcademicYear(name, startDate, endDate, description, isCurrent) {
                // Get the last year's data as base
                const currentYearData = academicYearsData.find(y => y.isCurrent);
                const lastYearIndex = academicYearsData.indexOf(currentYearData) + 1;
                const baseYear = lastYearIndex < academicYearsData.length ? academicYearsData[lastYearIndex] :
                    academicYearsData[0];

                // Generate random enrollment data
                const enrollmentData = generateRandomEnrollmentData('current');

                // Calculate start and end dates for terms
                const startDateObj = new Date(startDate);
                const endDateObj = new Date(endDate);

                // First term ends about 4 months after start
                const firstTermEnd = new Date(startDateObj);
                firstTermEnd.setMonth(firstTermEnd.getMonth() + 4);

                // Second term starts about 10 days after first term ends
                const secondTermStart = new Date(firstTermEnd);
                secondTermStart.setDate(secondTermStart.getDate() + 10);

                // Format dates as YYYY-MM-DD
                const formatDate = (date) => {
                    return date.toISOString().split('T')[0];
                };

                // Generate statistics
                const totalStudents = enrollmentData[enrollmentData.length - 1];
                const newEnrollees = Math.round(totalStudents * (Math.random() * 0.1 + 0.15)); // 15-25% of total
                const graduatingStudents = Math.round(totalStudents * (Math.random() * 0.05 +
                    0.1)); // 10-15% of total
                const attendance = Math.round(85 + Math.random() * 10); // 85-95%

                return {
                    id: academicYearsData.length + 1,
                    name,
                    startDate,
                    endDate,
                    isCurrent,
                    description,
                    enrollmentData,
                    terms: [{
                            name: 'First Term',
                            start: startDate,
                            end: formatDate(firstTermEnd)
                        },
                        {
                            name: 'Second Term',
                            start: formatDate(secondTermStart),
                            end: endDate
                        }
                    ],
                    statistics: {
                        totalStudents,
                        newEnrollees,
                        graduatingStudents,
                        attendance
                    },
                    events: [{
                            name: 'School Opening',
                            date: startDate
                        },
                        {
                            name: 'First Term Exams',
                            date: formatDate(new Date(firstTermEnd.getTime() - 5 * 24 * 60 * 60 * 1000))
                        },
                        {
                            name: 'Christmas Break',
                            date: '2025-12-20'
                        },
                        {
                            name: 'School Resumes',
                            date: '2026-01-06'
                        },
                        {
                            name: 'Final Exams',
                            date: formatDate(new Date(endDateObj.getTime() - 5 * 24 * 60 * 60 * 1000))
                        },
                        {
                            name: 'Graduation',
                            date: endDate
                        }
                    ]
                };
            }

            // Helper function to update school year management UI
            function updateAcademicYearUI() {
                const currentYear = academicYearsData.find(y => y.isCurrent);
                if (!currentYear) return;

                // Update current year display
                const currentAcademicYearElem = document.getElementById('currentAcademicYear');
                if (currentAcademicYearElem) {
                    currentAcademicYearElem.textContent = currentYear.name;
                }

                // Update duration
                const currentAcademicYearDurationElem = document.getElementById('currentAcademicYearDuration');
                if (currentAcademicYearDurationElem) {
                    const startDate = new Date(currentYear.startDate);
                    const endDate = new Date(currentYear.endDate);
                    const formatDate = (date) => {
                        return date.toLocaleString('default', {
                            month: 'short',
                            year: 'numeric'
                        });
                    };
                    currentAcademicYearDurationElem.textContent =
                        `${formatDate(startDate)} - ${formatDate(endDate)}`;
                }

                // Update school year table if it exists
                updateAcademicYearTable();

                // Update timeline if it exists
                updateAcademicYearTimeline();
            }

            // Initialize Enrollment Chart with data from current school year
            const currentYear = academicYearsData.find(y => y.isCurrent);
            const enrollmentData = {
                months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                series: [{
                    name: 'Students',
                    data: currentYear ? currentYear.enrollmentData : academicYearEnrollmentData.current
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
            let enrollmentChart;

            if (enrollmentChartElement) {
                enrollmentChartElement.innerHTML = '';
                enrollmentChart = new ApexCharts(enrollmentChartElement, enrollmentOptions);
                enrollmentChart.render();

                // Initialize total enrollment with current year data
                const totalEnrollmentElement = document.getElementById('totalEnrollment');
                if (totalEnrollmentElement) {
                    // Use the last month's data as current total
                    const currentYear = academicYearsData.find(y => y.isCurrent);
                    totalEnrollmentElement.textContent = currentYear ? currentYear.statistics.totalStudents :
                        academicYearEnrollmentData.current[11];
                }

                // Also update the current selected year element if it exists
                const currentSelectedYearElement = document.getElementById('currentSelectedYear');
                if (currentSelectedYearElement) {
                    const currentYear = academicYearsData.find(y => y.isCurrent);
                    currentSelectedYearElement.textContent = currentYear ? currentYear.name : '2025-2026';
                }

                // Enhanced function to update the school year table
                function updateAcademicYearTable() {
                    const tableBody = document.getElementById('academicYearTableBody');
                    if (!tableBody) return;

                    tableBody.innerHTML = '';

                    // Sort academic years by start date (newest first)
                    const sortedYears = [...academicYearsData].sort((a, b) =>
                        new Date(b.startDate) - new Date(a.startDate)
                    );

                    sortedYears.forEach(year => {
                        const row = document.createElement('tr');

                        // Format dates
                        const formatDate = (dateStr) => {
                            const date = new Date(dateStr);
                            return date.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        };

                        // Create cell content
                        row.innerHTML = `
                            <td>
                                <div class="fw-semibold">${year.name}</div>
                                <div class="text-muted small">${year.description}</div>
                            </td>
                            <td>${formatDate(year.startDate)}</td>
                            <td>${formatDate(year.endDate)}</td>
                            <td>${year.isCurrent ?
                                '<span class="badge bg-success">Current</span>' :
                                '<span class="badge bg-secondary">Inactive</span>'}</td>
                            <td>
                                <div class="d-flex">
                                    ${!year.isCurrent ?
                                        `<button class="btn btn-sm btn-outline-primary me-2 set-current-btn" data-id="${year.id}" data-bs-toggle="tooltip" title="Set as Current">
                                                                                                                            <i data-feather="check" class="feather-sm"></i>
                                                                                                                        </button>` : ''}
                                    <button class="btn btn-sm btn-outline-secondary me-2 view-btn" data-id="${year.id}" data-bs-toggle="tooltip" title="View Details">
                                        <i data-feather="eye" class="feather-sm"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary me-2 edit-btn" data-id="${year.id}" data-bs-toggle="tooltip" title="Edit">
                                        <i data-feather="edit-2" class="feather-sm"></i>
                                    </button>
                                    ${!year.isCurrent ?
                                        `<button class="btn btn-sm btn-outline-danger delete-btn" data-id="${year.id}" data-bs-toggle="tooltip" title="Delete">
                                                                                                                            <i data-feather="trash-2" class="feather-sm"></i>
                                                                                                                        </button>` : ''}
                                </div>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });

                    // Reinitialize feather icons for new buttons
                    feather.replace();

                    // Initialize tooltips
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll(
                        '[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });

                    // Attach event listeners
                    attachAcademicYearTableEventListeners();
                }

                // Enhanced function to update the school year timeline
                function updateAcademicYearTimeline() {
                    const timelineElem = document.getElementById('academicYearTimeline');
                    if (!timelineElem) return;

                    // Clear previous content
                    timelineElem.innerHTML = '';

                    // Create timeline container
                    const timeline = document.createElement('div');
                    timeline.className = 'position-relative w-100 h-100';

                    // Sort years by start date
                    const sortedYears = [...academicYearsData].sort((a, b) =>
                        new Date(a.startDate) - new Date(b.startDate)
                    );

                    // Calculate timeline range
                    const firstDate = new Date(sortedYears[0].startDate);
                    const lastDate = new Date(sortedYears[sortedYears.length - 1].endDate);
                    const totalDays = (lastDate - firstDate) / (1000 * 60 * 60 * 24);

                    // Create timeline base line
                    const baseLine = document.createElement('div');
                    baseLine.className = 'position-absolute';
                    baseLine.style.height = '4px';
                    baseLine.style.backgroundColor = '#e9ecef';
                    baseLine.style.width = '100%';
                    baseLine.style.top = '32px';
                    baseLine.style.borderRadius = '2px';
                    timeline.appendChild(baseLine);

                    // Add year segments to timeline
                    sortedYears.forEach((year, index) => {
                        const startDate = new Date(year.startDate);
                        const endDate = new Date(year.endDate);

                        // Calculate position and width based on dates
                        const startPos = ((startDate - firstDate) / (1000 * 60 * 60 * 24)) / totalDays *
                            100;
                        const duration = (endDate - startDate) / (1000 * 60 * 60 * 24);
                        const width = (duration / totalDays) * 100;

                        // Create segment
                        const segment = document.createElement('div');
                        segment.className = 'position-absolute';
                        segment.style.height = '16px';
                        segment.style.backgroundColor = year.isCurrent ? '#0d6efd' : '#6c757d';
                        segment.style.left = `${startPos}%`;
                        segment.style.width = `${width}%`;
                        segment.style.top = '26px';
                        segment.style.borderRadius = '8px';
                        segment.style.cursor = 'pointer';
                        segment.setAttribute('data-year-id', year.id);
                        segment.setAttribute('data-bs-toggle', 'tooltip');
                        segment.setAttribute('title',
                            `${year.name}: ${new Date(year.startDate).toLocaleDateString()} - ${new Date(year.endDate).toLocaleDateString()}`
                        );

                        // Add label
                        const label = document.createElement('div');
                        label.className = 'position-absolute small';
                        label.textContent = year.name;
                        label.style.left = `${startPos + (width / 2)}%`;
                        label.style.transform = 'translateX(-50%)';
                        label.style.top = year.isCurrent ? '0' : '48px';
                        label.style.fontWeight = year.isCurrent ? 'bold' : 'normal';

                        // Add to timeline
                        timeline.appendChild(segment);
                        timeline.appendChild(label);

                        // Add term markers
                        if (year.terms) {
                            year.terms.forEach((term, termIndex) => {
                                const termDate = new Date(term.end);
                                const termPos = ((termDate - firstDate) / (1000 * 60 * 60 * 24)) /
                                    totalDays * 100;

                                if (termPos > startPos && termPos < (startPos + width)) {
                                    const termMarker = document.createElement('div');
                                    termMarker.className = 'position-absolute';
                                    termMarker.style.width = '3px';
                                    termMarker.style.height = '24px';
                                    termMarker.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
                                    termMarker.style.left = `${termPos}%`;
                                    termMarker.style.top = '22px';
                                    termMarker.setAttribute('data-bs-toggle', 'tooltip');
                                    termMarker.setAttribute('title',
                                        `${term.name} Ends: ${new Date(term.end).toLocaleDateString()}`
                                    );

                                    timeline.appendChild(termMarker);
                                }
                            });
                        }

                        // Add dot for current position if current year
                        if (year.isCurrent) {
                            const now = new Date();
                            if (now >= startDate && now <= endDate) {
                                const progress = ((now - startDate) / (1000 * 60 * 60 * 24)) / duration;
                                const currentPos = startPos + (width * progress);

                                const currentDot = document.createElement('div');
                                currentDot.className = 'position-absolute';
                                currentDot.style.height = '24px';
                                currentDot.style.width = '24px';
                                currentDot.style.backgroundColor = 'white';
                                currentDot.style.border = '3px solid #dc3545';
                                currentDot.style.left = `${currentPos}%`;
                                currentDot.style.top = '22px';
                                currentDot.style.borderRadius = '50%';
                                currentDot.style.transform = 'translateX(-50%)';
                                currentDot.style.zIndex = '2';
                                currentDot.style.boxShadow = '0 0 0 4px rgba(220, 53, 69, 0.3)';
                                currentDot.setAttribute('data-bs-toggle', 'tooltip');
                                currentDot.setAttribute('title', `Today: ${now.toLocaleDateString()}`);

                                timeline.appendChild(currentDot);
                            }
                        }

                        // Add event markers
                        if (year.events) {
                            year.events.forEach(event => {
                                const eventDate = new Date(event.date);
                                const eventPos = ((eventDate - firstDate) / (1000 * 60 * 60 * 24)) /
                                    totalDays * 100;

                                if (eventPos > startPos && eventPos < (startPos + width)) {
                                    const eventMarker = document.createElement('div');
                                    eventMarker.className = 'position-absolute';
                                    eventMarker.style.width = '10px';
                                    eventMarker.style.height = '10px';
                                    eventMarker.style.backgroundColor = '#ffc107';
                                    eventMarker.style.borderRadius = '50%';
                                    eventMarker.style.left = `${eventPos}%`;
                                    eventMarker.style.top = year.isCurrent ? '58px' : '10px';
                                    eventMarker.style.transform = 'translateX(-50%)';
                                    eventMarker.style.cursor = 'pointer';
                                    eventMarker.setAttribute('data-bs-toggle', 'tooltip');
                                    eventMarker.setAttribute('title',
                                        `${event.name}: ${eventDate.toLocaleDateString()}`);

                                    timeline.appendChild(eventMarker);
                                }
                            });
                        }

                        // Add click event to segment
                        segment.addEventListener('click', function() {
                            const yearId = this.getAttribute('data-year-id');
                            showAcademicYearDetails(yearId);
                        });
                    });

                    timelineElem.appendChild(timeline);

                    // Initialize tooltips
                    const tooltipTriggerList = [].slice.call(timelineElem.querySelectorAll(
                        '[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }

                // Initialize school year details modal view
                function showAcademicYearDetails(yearId) {
                    const year = academicYearsData.find(y => y.id == yearId);
                    if (!year) return;

                    // Format dates
                    const formatDate = (dateStr) => {
                        const date = new Date(dateStr);
                        return date.toLocaleDateString('en-US', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    };

                    // Find or create modal
                    let modal = document.getElementById('yearDetailsModal');
                    if (!modal) {
                        modal = document.createElement('div');
                        modal.className = 'modal fade';
                        modal.id = 'yearDetailsModal';
                        modal.tabIndex = '-1';
                        modal.setAttribute('aria-labelledby', 'yearDetailsModalLabel');
                        modal.setAttribute('aria-hidden', 'true');

                        modal.innerHTML = `
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="yearDetailsModalLabel">School Year Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="yearDetailsBody">
                                        <!-- Content will be filled dynamically -->
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        `;

                        document.body.appendChild(modal);
                    }

                    // Update modal content
                    const modalBody = document.getElementById('yearDetailsBody');
                    modalBody.innerHTML = `
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h3>${year.name} ${year.isCurrent ? '<span class="badge bg-success">Current</span>' : ''}</h3>
                                <p class="text-muted">${year.description}</p>
                                <div class="mb-3">
                                    <strong>Duration:</strong> ${formatDate(year.startDate)} to ${formatDate(year.endDate)}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Statistics</h5>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="border-start border-4 border-primary ps-3">
                                                    <div class="text-muted small">Total Students</div>
                                                    <div class="fs-4">${year.statistics.totalStudents}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border-start border-4 border-success ps-3">
                                                    <div class="text-muted small">New Enrollees</div>
                                                    <div class="fs-4">${year.statistics.newEnrollees}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border-start border-4 border-warning ps-3">
                                                    <div class="text-muted small">Graduating Students</div>
                                                    <div class="fs-4">${year.statistics.graduatingStudents}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border-start border-4 border-info ps-3">
                                                    <div class="text-muted small">Attendance Rate</div>
                                                    <div class="fs-4">${year.statistics.attendance}%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3">Academic Terms</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Term Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${year.terms.map(term => {
                                        const start = new Date(term.start);
                                        const end = new Date(term.end);
                                        const durationDays = Math.round((end - start) / (1000 * 60 * 60 * 24));
                                        const durationWeeks = Math.floor(durationDays / 7);
                                        return `
                                                                                                                            <tr>
                                                                                                                                <td>${term.name}</td>
                                                                                                                                <td>${formatDate(term.start)}</td>
                                                                                                                                <td>${formatDate(term.end)}</td>
                                                                                                                                <td>${durationWeeks} weeks (${durationDays} days)</td>
                                                                                                                            </tr>
                                                                                                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Important Events</h5>
                                <ul class="list-group">
                                    ${year.events.map(event => `
                                                                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                            <div>${event.name}</div>
                                                                                                                            <span class="badge bg-primary rounded-pill">${formatDate(event.date)}</span>
                                                                                                                        </li>
                                                                                                                    `).join('')}
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Enrollment Trends</h5>
                                <div id="yearDetailsEnrollmentChart" style="height: 200px;"></div>
                            </div>
                        </div>
                    `;

                    // Initialize the modal
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();

                    // Initialize enrollment trends chart
                    setTimeout(() => {
                        const chartElem = document.getElementById('yearDetailsEnrollmentChart');
                        if (chartElem) {
                            const enrollmentChart = new ApexCharts(chartElem, {
                                chart: {
                                    type: 'area',
                                    height: 200,
                                    toolbar: {
                                        show: false
                                    },
                                    sparkline: {
                                        enabled: false
                                    }
                                },
                                stroke: {
                                    curve: 'smooth',
                                    width: 2
                                },
                                colors: ['#0d6efd'],
                                series: [{
                                    name: 'Students',
                                    data: year.enrollmentData
                                }],
                                xaxis: {
                                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul',
                                        'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                                    ]
                                },
                                yaxis: {
                                    labels: {
                                        show: false
                                    }
                                },
                                fill: {
                                    opacity: 0.3
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
                                }
                            });
                            enrollmentChart.render();
                        }
                    }, 300);
                }

                // Function to attach event listeners to school year table buttons
                function attachAcademicYearTableEventListeners() {
                    // Set current buttons
                    document.querySelectorAll('.set-current-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const yearId = this.getAttribute('data-id');
                            setAcademicYearAsCurrent(yearId);
                        });
                    });

                    // View buttons
                    document.querySelectorAll('.view-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const yearId = this.getAttribute('data-id');
                            showAcademicYearDetails(yearId);
                        });
                    });

                    // Edit buttons
                    document.querySelectorAll('.edit-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const yearId = this.getAttribute('data-id');
                            openEditModal(yearId);
                        });
                    });

                    // Delete buttons
                    document.querySelectorAll('.delete-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const yearId = this.getAttribute('data-id');
                            openDeleteModal(yearId);
                        });
                    });
                }

                // Function to set school year as current
                function setAcademicYearAsCurrent(yearId) {
                    // Update the data model
                    academicYearsData.forEach(year => {
                        year.isCurrent = (year.id == yearId);
                    });

                    // Update the UI
                    updateAcademicYearUI();

                    // Update enrollment dropdown if it exists
                    const enrollmentYearSelect = document.getElementById('enrollmentYearSelect');
                    if (enrollmentYearSelect) {
                        const currentYear = academicYearsData.find(y => y.isCurrent);
                        enrollmentYearSelect.querySelectorAll('option').forEach(option => {
                            if (option.textContent.includes('Current')) {
                                option.textContent = option.textContent.replace(/\([^)]*\)/, '');
                            }

                            if (option.textContent.includes(currentYear.name)) {
                                option.textContent = `${currentYear.name} (Current)`;
                                option.value = 'current';
                                option.selected = true;
                            }
                        });

                        // Trigger change event to update chart
                        const event = new Event('change');
                        enrollmentYearSelect.dispatchEvent(event);
                    }

                    // Show success message
                    showToast('Academic year set as current successfully', 'success');
                }

                // Call initial update for school year UI
                updateAcademicYearUI();

                // Add event listener for school year dropdown
                const enrollmentYearSelect = document.getElementById('enrollmentYearSelect');
                if (enrollmentYearSelect) {
                    // Populate dropdown with academic years from our enhanced data structure
                    enrollmentYearSelect.innerHTML = '';

                    // Add current year option
                    const currentYear = academicYearsData.find(y => y.isCurrent);
                    const currentOption = document.createElement('option');
                    currentOption.value = 'current';
                    currentOption.textContent = `${currentYear.name} (Current)`;
                    currentOption.selected = true;
                    enrollmentYearSelect.appendChild(currentOption);

                    // Add other years
                    academicYearsData.filter(y => !y.isCurrent).forEach(year => {
                        const option = document.createElement('option');
                        option.value = year.name;
                        option.textContent = year.name;
                        enrollmentYearSelect.appendChild(option);
                    });

                    // Add event listener
                    enrollmentYearSelect.addEventListener('change', function() {
                        const selectedYear = this.value;
                        let yearData;
                        let yearName;

                        // Find the selected year in our data structure
                        if (selectedYear === 'current') {
                            const currentYear = academicYearsData.find(y => y.isCurrent);
                            yearData = currentYear.enrollmentData;
                            yearName = currentYear.name;
                        } else {
                            const year = academicYearsData.find(y => y.name === selectedYear);
                            if (year) {
                                yearData = year.enrollmentData;
                                yearName = year.name;
                            } else {
                                // Generate random data for this school year if not found
                                yearData = generateRandomEnrollmentData('current');

                                // Create a new school year entry
                                const newYear = generateNewAcademicYear(
                                    selectedYear,
                                    `${selectedYear.split('-')[0]}-06-15`, // Estimate start date
                                    `${selectedYear.split('-')[1]}-03-31`, // Estimate end date
                                    `Academic year ${selectedYear}`,
                                    false
                                );
                                newYear.enrollmentData = yearData;
                                academicYearsData.push(newYear);
                                yearName = newYear.name;
                            }
                        }

                        // Update chart with new data
                        enrollmentChart.updateSeries([{
                            name: 'Students',
                            data: yearData
                        }]);

                        // Update the total enrollment number
                        const totalEnrollmentElement = document.getElementById('totalEnrollment');
                        if (totalEnrollmentElement) {
                            // Calculate total from last month's data
                            const totalEnrollment = yearData[yearData.length - 1];
                            totalEnrollmentElement.textContent = totalEnrollment;
                        }

                        // Update enrollment labels with selected year
                        const yearLabel = document.getElementById('selectedAcademicYearLabel');
                        if (yearLabel) {
                            yearLabel.textContent = yearName;
                        }

                        // Update the current selected year text if it exists
                        const currentSelectedYearElement = document.getElementById('currentSelectedYear');
                        if (currentSelectedYearElement) {
                            currentSelectedYearElement.textContent = yearName;
                        }
                    });
                }
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
