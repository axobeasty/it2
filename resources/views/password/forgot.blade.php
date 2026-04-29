<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>Восстановление пароля — {{ $settings?->title ?? 'Система' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        body { background: #eaeff6; font-family: 'Segoe UI', sans-serif; min-height: 100dvh; margin: 0; }
        .card { border-radius: 12px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); border: none; max-width: 420px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
<div class="w-100" style="max-width: 440px;">
    @if($settings !== null && (int) $settings->is_enabled === 0)
        <div class="card">
            <div class="card-body text-center p-4">
                <h5 class="mb-3">Техническое обслуживание</h5>
                <p class="small text-muted mb-0">{{ $settings->disable_reason ?: 'Система временно недоступна.' }}</p>
                <a href="/" class="btn btn-outline-primary rounded-pill mt-3">На страницу входа</a>
            </div>
        </div>
    @else
        <div class="card mx-auto">
            <div class="card-body p-4">
                <h1 class="h5 mb-1">Восстановление пароля</h1>
                <p class="small text-muted mb-4">Укажите e-mail, привязанный к учётной записи. Пришлём ссылку для нового пароля.</p>
                <form action="{{ route('password.forgot.send') }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Отправить ссылку</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="/" class="small">← Вернуться ко входу</a>
                </p>
            </div>
        </div>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
