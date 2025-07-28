<div class="top-bar">
    <button id="toggleBtn" class="hamburger">
        <i data-feather="menu"></i>
    </button>
    <h1 class="h3 mb-0 school-title">Tagurot Elementary School</h1>
    <div class="ms-auto d-flex align-items-center" style="gap: 1.5rem;">
        <div class="text-white time-container">
            <div id="ph-time" class="text-end"></div>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown user-dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                {{-- <span class="me-2 admin-text">{{ Auth::user()->first_name }}</span> --}}
                <span class="me-2 admin-text">{{ Auth::user()->first_name }} </span>
                <span data-feather="user"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="userDropdown">
                @if (Auth::user()->role === 'admin')
                    <li><a class="dropdown-item" href="{{ url('/admin/settings') }}">Settings</a></li>
                @endif
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign
                        out</a>
                </li>
            </ul>
        </div>
    </div>
</div>
