<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center" id="logoutModalLabel">
                    <i data-feather="alert-triangle" class="me-2 text-danger"></i>
                    Confirm Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-0 text-center">Are you sure you want to log out of your account?</p>
                @if (Auth::check())
                    <p class="text-center text-muted mt-2">
                        Logged in as: <strong>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</strong>
                    </p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center"
                    data-bs-dismiss="modal">
                    <i data-feather="x" class="feather-sm me-1"></i> Cancel
                </button>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger d-flex align-items-center">
                        <i data-feather="log-out" class="feather-sm me-1"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
