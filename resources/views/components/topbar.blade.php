@php
    $currentSchoolYear = \App\Models\SchoolYear::getActive();
    $realActiveSchoolYear = \App\Models\SchoolYear::getRealActive();
    $currentQuarter = \App\Models\SchoolYear::getCurrentQuarter();
    $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
    $isAdminViewMode = $isAdmin &&
        session()->has(\App\Models\SchoolYear::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY) &&
        $currentSchoolYear &&
        $realActiveSchoolYear &&
        (int) $currentSchoolYear->id !== (int) $realActiveSchoolYear->id;
@endphp
<div class="top-bar">
    <button id="toggleBtn" class="hamburger" aria-label="Toggle sidebar" title="Toggle sidebar">
        <i data-feather="menu" aria-hidden="true"></i>
        <span class="visually-hidden">Toggle sidebar</span>
    </button>
    <h1 class="h3 mb-0 school-title">Tagurot Elementary School</h1>
    @if ($currentSchoolYear)
        <span class="text-white ms-3" style="font-size: 0.9rem;">
            SY{{ $currentSchoolYear->name }} ({{ $currentQuarter ? $currentQuarter->name : 'No Active Quarter' }})
        </span>
    @endif
    @if ($isAdminViewMode)
        <span class="badge bg-warning text-dark ms-2">Admin View Mode</span>
        <form method="POST" action="{{ route('admin.school-year.view.reset') }}" class="ms-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-light">
                Back To Active SY ({{ $realActiveSchoolYear->name }})
            </button>
        </form>
    @endif
    <div class="ms-auto d-flex align-items-center" style="gap: 1.5rem;">
        <div class="text-white time-container">
            <div id="ph-time" class="text-end"></div>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown user-dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                @if (Auth::user()->profile_picture)
                    <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}"
                        alt="{{ Auth::user()->first_name }}" class="rounded-circle me-2"
                        style="width:32px;height:32px;object-fit:cover;" />
                    <span class="me-2 admin-text">{{ Auth::user()->first_name }}</span>
                @else
                    <span class="me-2 admin-text">{{ Auth::user()->first_name }}</span>
                    <span
                        class="avatar-placeholder rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center"
                        style="width:32px;height:32px;font-size:0.85rem;">{{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}</span>
                @endif
            </a>
            <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="{{ route('profile') }}"><i data-feather="user"
                            class="icon-sm me-2"></i>My Profile</a></li>
                @if (Auth::user()->role === 'admin')
                    <li><a class="dropdown-item" href="{{ url('/admin/settings') }}"><i data-feather="settings"
                                class="icon-sm me-2"></i>Settings</a></li>
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
