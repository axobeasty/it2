<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title ?? 'Техническое обслуживание' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(165deg, #e8eef8 0%, #eef2fa 50%, #e2e8f4 100%);
        }
        .maint-card {
            max-width: 520px;
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.08);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-4">
    <div class="card maint-card w-100">
        <div class="card-body text-center p-4 p-md-5">
            <div class="rounded-circle bg-warning bg-opacity-15 text-warning d-inline-flex align-items-center justify-content-center mb-4" style="width:4.5rem;height:4.5rem;">
                <i class="bi bi-wrench-adjustable fs-2"></i>
            </div>
            <h1 class="h4 fw-semibold text-dark mb-2">Техническое обслуживание</h1>
            <p class="text-muted small mb-4">{{ $settings->title ?? '' }}</p>
            <div class="rounded-3 bg-light border p-4 text-dark text-start">
                <p class="mb-0 lead fs-6">{{ $settings->disable_reason }}</p>
            </div>
            <p class="small text-muted mt-4 mb-0">Пожалуйста, зайдите позже.</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
