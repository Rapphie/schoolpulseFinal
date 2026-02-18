<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Maintenance Mode</title>
    @include('components.head')
    <style>
        :root {
            --maintenance-primary: #0d6efd;
            --maintenance-primary-soft: rgba(13, 110, 253, 0.12);
            --maintenance-secondary: #0dcaf0;
            --maintenance-text: #1f2937;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #f7faff 0%, #eef4ff 45%, #e7f6ff 100%);
            color: var(--maintenance-text);
            overflow: hidden;
        }

        .maintenance-wrap {
            position: relative;
            width: 100%;
            max-width: 760px;
        }

        .maintenance-orb {
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
            z-index: 0;
        }

        .maintenance-orb.one {
            width: 180px;
            height: 180px;
            top: -70px;
            left: -60px;
            background: rgba(13, 110, 253, 0.22);
        }

        .maintenance-orb.two {
            width: 220px;
            height: 220px;
            right: -80px;
            bottom: -90px;
            background: rgba(13, 202, 240, 0.2);
        }

        .maintenance-card {
            position: relative;
            z-index: 1;
            border: 1px solid rgba(13, 110, 253, 0.2);
            border-radius: 1.25rem;
            box-shadow: 0 24px 48px rgba(13, 110, 253, 0.12);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(6px);
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 999px;
            padding: 0.5rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #0a58ca;
            background: var(--maintenance-primary-soft);
        }

        .code-display {
            line-height: 1;
            font-weight: 800;
            font-size: clamp(3rem, 9vw, 4.8rem);
            letter-spacing: -0.08em;
            background: linear-gradient(90deg, var(--maintenance-primary), var(--maintenance-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .subtitle {
            max-width: 38rem;
            margin: 0 auto;
        }

        .btn-maintenance {
            border: none;
            font-weight: 600;
            border-radius: 0.8rem;
            padding: 0.75rem 1.2rem;
            background: linear-gradient(90deg, #0d6efd 0%, #0b5ed7 100%);
            box-shadow: 0 12px 24px rgba(13, 110, 253, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-maintenance:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(13, 110, 253, 0.3);
        }

        .btn-outline-maintenance {
            border-radius: 0.8rem;
            border-color: rgba(13, 110, 253, 0.35);
            color: #0a58ca;
            font-weight: 600;
            padding: 0.75rem 1.2rem;
        }

        .btn-outline-maintenance:hover {
            background-color: rgba(13, 110, 253, 0.08);
            color: #0a58ca;
            border-color: rgba(13, 110, 253, 0.5);
        }

        @media (max-width: 576px) {
            .maintenance-card {
                border-radius: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container px-3 px-md-4">
        <div class="maintenance-wrap mx-auto">
            <span class="maintenance-orb one" aria-hidden="true"></span>
            <span class="maintenance-orb two" aria-hidden="true"></span>

            <div class="card maintenance-card mx-auto">
                <div class="card-body p-4 p-md-5 text-center">
                    <span class="status-chip mb-3">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        Maintenance in progress
                    </span>

                    <div class="code-display">503</div>
                    <h1 class="h3 fw-bold mt-2">System Maintenance</h1>
                    <p class="text-muted subtitle mb-4">
                        We are applying updates to improve your experience. Please check back shortly.
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button type="button" onclick="window.location.reload()" class="btn btn-primary btn-maintenance">
                            <i class="fa-solid fa-rotate-right me-1"></i> Refresh Page
                        </button>
                        <a href="javascript:history.back()" class="btn btn-outline-primary btn-outline-maintenance">
                            <i class="fa-solid fa-arrow-left me-1"></i> Go Back
                        </a>
                    </div>

                    <p class="small text-muted mt-3 mb-0">Thanks for your patience while we complete the update.</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
