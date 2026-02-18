<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Maintenance</title>
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
                <div class="display-5 code text-info">503</div>
                <h1 class="h3 fw-bold mt-2">System Maintenance</h1>
                <p class="text-muted mb-4">We are doing a quick maintenance check. Please try again in a few minutes.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" onclick="window.location.reload()" class="btn btn-primary">
                        <i class="fa-solid fa-rotate-right me-1"></i> Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        feather.replace()
    </script>
</body>

</html>
