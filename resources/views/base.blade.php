<!DOCTYPE html>
<html lang="en">

@php
    use Illuminate\Support\Facades\Auth;

    $isGuardianLayout = Auth::check() && Auth::user()->hasRole('guardian');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SchoolPulse')</title>
    @yield('head')
    @include('components.head')
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">

    @stack('styles')
</head>

<body class="bg-light min-vh-100 {{ $isGuardianLayout ? 'guardian-layout' : '' }}">
    @include('components.topbar')

    <div id="content" class="w-100">
        @include('components.sidebar')
        <div class="sidebar-overlay"></div>

        <main>
            {{-- Toast notification container (fixed, top-right) --}}
            <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show session-alert" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center session-alert"
                    role="alert">
                    <i data-feather="alert-circle" class="icon-sm me-2"></i>
                    <span>{{ session('warning') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center session-alert"
                    role="alert" style="border-left: 4px solid #dc3545; font-weight: 500;">
                    <i data-feather="alert-triangle" class="icon-sm me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show session-alert" role="alert"
                    style="border-left: 4px solid #dc3545; font-weight: 500;">
                    <div class="d-flex align-items-start">
                        <i data-feather="alert-triangle" class="icon-sm me-2 mt-1"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        @include('components.modals.logout-modal')
    </div>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/angular.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('css/feather.min.js') }}"></script>

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
                timeElement.innerHTML =
                    '<div class="text-end">' + timeString + '</div>' +
                    '<div class="small">' + dateString + ' (PHT)</div>';
            }
        }

        // Update time immediately and then every second
        updatePhilippineTime();
        setInterval(updatePhilippineTime, 1000);
    </script>

    <script>
        // Initialize Feather Icons and simplify sidebar toggle behavior
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();

            const toggleBtn = document.getElementById('toggleBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            const content = document.getElementById('content');
            const topBar = document.querySelector('.top-bar');
            const overlay = document.querySelector('.sidebar-overlay');

            const isMobile = () => window.innerWidth < 768;

            const openMobile = () => {
                if (!sidebar) return;
                sidebar.classList.add('show');
                sidebar.classList.remove('collapsed');
                document.body.style.overflow = 'hidden';
            };

            const closeMobile = () => {
                if (!sidebar) return;
                sidebar.classList.remove('show');
                sidebar.classList.add('collapsed');
                document.body.style.overflow = '';
            };

            // Initial state for mobile
            if (sidebar && isMobile()) {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
            }

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    if (isMobile()) {
                        sidebar.classList.contains('show') ? closeMobile() : openMobile();
                        return;
                    }

                    const collapsed = sidebar.classList.toggle('collapsed');
                    if (collapsed) sidebar.classList.remove('show');
                    else sidebar.classList.add('show');
                    if (content) content.classList.toggle('content-shifted');
                    if (topBar) topBar.classList.toggle('content-shifted');
                });
            }

            if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeMobile);
            if (overlay) overlay.addEventListener('click', closeMobile);

            document.addEventListener('click', function(event) {
                if (!sidebar) return;
                const clickInside = sidebar.contains(event.target);
                const clickToggle = toggleBtn && toggleBtn.contains(event.target);
                const clickOverlay = overlay && overlay.contains(event.target);
                if (!clickInside && !clickToggle && !clickOverlay && isMobile() && sidebar.classList
                    .contains('show')) {
                    closeMobile();
                }
            });

            const reinitFeather = () => feather.replace();
            document.querySelectorAll('.dropdown').forEach(d => d.addEventListener('shown.bs.dropdown',
                reinitFeather));
            document.querySelectorAll('.modal').forEach(m => m.addEventListener('shown.bs.modal', reinitFeather));
            document.querySelectorAll('.sidebar .collapse').forEach(s => s.addEventListener('shown.bs.collapse',
                reinitFeather));

            // Auto-open active submenu on page load
            document.querySelectorAll('.sidebar .collapse.show').forEach(menu => {
                const toggler = document.querySelector('[href="#' + menu.id + '"]');
                if (toggler) toggler.setAttribute('aria-expanded', 'true');
            });

            window.addEventListener('resize', function() {
                if (isMobile()) {
                    if (topBar) topBar.classList.remove('content-shifted');
                    if (content) content.classList.remove('content-shifted');
                    if (sidebar && !sidebar.classList.contains('show')) sidebar.classList.add('collapsed');
                } else {
                    if (sidebar && sidebar.classList.contains('collapsed')) {
                        if (topBar) topBar.classList.add('content-shifted');
                        if (content) content.classList.add('content-shifted');
                    } else {
                        if (topBar) topBar.classList.remove('content-shifted');
                        if (content) content.classList.remove('content-shifted');
                    }
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    @stack('scripts')

    <script>
        /**
         * Global toast notification helper.
         * Usage: showToast('Score saved!', 'success');
         *        showToast('Something went wrong.', 'danger');
         */
        window.showToast = function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const icons = {
                success: 'check-circle',
                danger: 'alert-triangle',
                warning: 'alert-circle',
                info: 'info',
            };
            const bgColors = {
                success: '#198754',
                danger: '#dc3545',
                warning: '#ffc107',
                info: '#0d6efd',
            };

            const toastId = 'toast-' + Date.now();
            const iconName = icons[type] || icons.info;
            const bgColor = bgColors[type] || bgColors.info;
            const textColor = type === 'warning' ? '#000' : '#fff';

            const toastHTML = `
                <div id="${toastId}" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true"
                     style="background-color: ${bgColor}; color: ${textColor}; min-width: 300px;">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i data-feather="${iconName}" style="width:18px;height:18px;"></i>
                            <span>${message}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', toastHTML);
            const toastEl = document.getElementById(toastId);
            if (typeof feather !== 'undefined') feather.replace();
            const toast = new bootstrap.Toast(toastEl, {
                delay: duration
            });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        };

        // Auto-scroll to session alerts so they are always visible
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.session-alert');
            if (alert) {
                alert.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        });
    </script>
</body>

</html>
