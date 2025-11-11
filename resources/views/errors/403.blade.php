<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Forbidden</title>
    @include('components.head')
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f8fafc;
        }

        .error-card {
            max-width: 680px;
            width: 100%;
        }

        .code {
            font-weight: 800;
            font-size: 4rem;
        }
    </style>
</head>

<body>
    <div class="container px-3">
        <div class="card shadow-sm border-0 mx-auto error-card">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="display-5 code text-danger">403</div>
                <h1 class="h3 fw-bold mt-2">Access Denied</h1>
                <p class="text-muted mb-4">You don't have permission to view this page.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        <i class="fa-solid fa-house me-1"></i> Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        feather.replace()
    </script>
</body>

</html>
