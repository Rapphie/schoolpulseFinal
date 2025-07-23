<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SchoolPulse')</title>
    @yield('head')
    @include('components.head')

    <style>
        @font-face {
            font-family: 'Inter';
            src: url('{{ Vite::asset('resources/fonts/inter/Inter-Regular.woff2') }}') format('woff2'),
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
        }

        @media (max-width: 767.98px) {
            .sidebar.show~#content .sidebar-overlay {
                display: block;
                overflow-y: auto;
            }

            .sidebar.show {
                overflow-y: auto;
                max-height: 100vh;
            }
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
            z-index: 1040;
            display: flex;
            flex-direction: column;
        }

        /* Mobile sidebar close button */
        .sidebar-close {
            display: none;
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(13, 110, 253, 0.7);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1070;
        }

        @media (max-width: 767.98px) {
            .sidebar-close {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 1rem 0.5rem;
            overflow-y: auto;
            max-height: 100vh;
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

        .sidebar.collapsed .nav-link {
            overflow: visible !important;
        }

        .sidebar.collapsed .dropdown.show {
            overflow: visible !important;
        }

        .sidebar.collapsed .dropdown .dropdown-menu.show {
            position: fixed !important;
            left: 70px !important;
            display: block;
        }

        /* Special styling for logo in mini sidebar */
        .sidebar.collapsed .brand img {
            margin: 0 auto !important;
            padding: 0 !important;
            display: block;
        }

        /* Content adjustment when sidebar is collapsed */
        .top-bar.content-shifted {
            padding-left: 80px !important;
        }

        .content-shifted main {
            margin-left: 80px !important;
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

        /* Mobile-specific styles */
        @media (max-width: 767.98px) {

            /* Sidebar styles for mobile */
            .sidebar {
                width: 100% !important;
                /* Full width on mobile */
                transform: translateX(-100%);
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1060;
            }

            .sidebar.collapsed {
                width: 100% !important;
                /* Keep full width even when collapsed */
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
                width: 100% !important;
            }

            /* Show all text elements when sidebar is shown on mobile */
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

            /* Topbar adjustments for mobile */
            .top-bar {
                padding-left: 60px !important;
                /* Space for hamburger */
                justify-content: space-between;
            }

            .top-bar.content-shifted {
                padding-left: 60px !important;
                /* Keep consistent spacing */
            }

            /* Ensure hamburger is always visible and properly positioned on mobile */
            .hamburger {
                position: fixed;
                left: 10px;
                top: 10px;
                z-index: 1070;
                display: flex !important;
                /* Always show hamburger */
                background-color: rgba(13, 110, 253, 0.7);
                /* Make it more visible */
                width: 40px;
                height: 40px;
                border-radius: 4px;
                align-items: center;
                justify-content: center;
            }

            /* Move hamburger when sidebar is open */
            .sidebar.show~#content .hamburger {
                visibility: hidden;
                /* Hide hamburger when sidebar is open */
            }

            /* Always fully collapse main content on mobile */
            .content-shifted main {
                margin-left: 0 !important;
            }

            main {
                margin-left: 0 !important;
            }
        }

        /* Small screens */
        @media (max-width: 820px) {

            /* Hide school title and time on small screens */
            .school-title {
                display: none;
            }

            .time-container {
                display: none;
            }

            /* Center the remaining topbar content */
            .top-bar {
                justify-content: center;
            }
        }

        main {
            margin-left: 250px;
            padding: 1rem;
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
            color: white;
        }

        /* Make sure submenu styling works in mini sidebar mode */
        .sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }
    </style>
    @stack('styles')
</head>

<body class="bg-light">
    @include('components.topbar')

    <div id="content" class="w-100">
        @include('components.sidebar')
        <div class="sidebar-overlay"></div>

        <main>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i data-feather="alert-triangle" class="icon-sm me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
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
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            const content = document.getElementById('content');
            const mainContent = document.querySelector('main');
            const topBar = document.querySelector('.top-bar');

            // Ensure sidebar is closed by default on mobile
            const isMobile = window.innerWidth < 768;
            if (isMobile) {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
            }

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    const isMobile = window.innerWidth < 768;

                    if (isMobile) {
                        // On mobile, just toggle show/hide for fullscreen sidebar
                        sidebar.classList.toggle('show');

                        // Ensure collapsed class is managed properly
                        if (sidebar.classList.contains('show')) {
                            sidebar.classList.remove('collapsed');
                        } else {
                            sidebar.classList.add('collapsed');
                        }

                        // Prevent body scrolling when sidebar is open
                        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
                    } else {
                        // On desktop, toggle collapsed state
                        sidebar.classList.toggle('collapsed');

                        // Always keep show class on desktop when sidebar is visible
                        if (sidebar.classList.contains('collapsed')) {
                            sidebar.classList.remove('show');
                        } else {
                            sidebar.classList.add('show');
                        }

                        // Toggle content-shifted class for desktop layout
                        content.classList.toggle('content-shifted');
                        if (topBar) {
                            topBar.classList.toggle('content-shifted');
                        }
                    }
                });
            } // Close sidebar when clicking close button
            if (sidebarCloseBtn) {
                sidebarCloseBtn.addEventListener('click', function() {
                    if (sidebar) {
                        sidebar.classList.remove('show');
                        sidebar.classList.add('collapsed');
                        document.body.style.overflow = '';
                    }
                });
            }

            // Close sidebar when clicking outside or on overlay
            const overlay = document.querySelector('.sidebar-overlay');

            if (overlay) {
                overlay.addEventListener('click', function() {
                    if (sidebar && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        sidebar.classList.add('collapsed');
                        document.body.style.overflow = '';
                    }
                });
            }

            document.addEventListener('click', function(event) {
                if (sidebar && toggleBtn) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggleBtn = toggleBtn.contains(event.target);
                    const isClickOnOverlay = overlay && overlay.contains(event.target);
                    const isMobile = window.innerWidth < 768;

                    if (!isClickInsideSidebar && !isClickOnToggleBtn && !isClickOnOverlay) {
                        if (isMobile && sidebar.classList.contains('show')) {
                            // For mobile, hide the sidebar
                            sidebar.classList.remove('show');
                            sidebar.classList.add('collapsed');
                            document.body.style.overflow = '';
                        } else if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                            // For small desktop, hide sidebar
                            sidebar.classList.remove('show');
                        }
                    }
                }
            });


            // Re-initialize Feather icons when dropdowns or modals are shown
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('shown.bs.dropdown', function() {
                    feather.replace();
                });
            });

            // Initialize Feather icons in modals when they're shown
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
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

            // Handle window resize events
            window.addEventListener('resize', function() {
                const isMobile = window.innerWidth < 768;

                if (isMobile) {
                    // On mobile, make sure topbar and main are correctly positioned
                    if (topBar) {
                        topBar.classList.remove('content-shifted');
                    }
                    // Ensure main content has no margin
                    mainContent.style.marginLeft = '0px';
                } else {
                    // On desktop, restore proper classes based on sidebar state
                    if (sidebar.classList.contains('collapsed')) {
                        if (topBar) {
                            topBar.classList.add('content-shifted');
                        }
                        content.classList.add('content-shifted');
                    } else {
                        if (topBar) {
                            topBar.classList.remove('content-shifted');
                        }
                        content.classList.remove('content-shifted');
                    }
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
