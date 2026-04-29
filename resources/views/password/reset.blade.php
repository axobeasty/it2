<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>Новый пароль — {{ $settings?->title ?? 'Система' }}</title>
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
        <div class="card mx-auto">
            <div class="card-body text-center p-4">
                <h5 class="mb-3">Техническое обслуживание</h5>
                <p class="small text-muted mb-0">После окончания работ вы сможете войти с новым паролем.</p>
                <a href="/" class="btn btn-outline-primary rounded-pill mt-3">На страницу входа</a>
            </div>
        </div>
    @else
        <div class="card mx-auto">
            <div class="card-body p-4">
                <h1 class="h5 mb-1">Новый пароль</h1>
                <p class="small text-muted mb-4">Придумайте пароль не короче 8 символов.</p>
                <form action="{{ route('password.reset.submit') }}" method="post">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                               id="password" name="password" required autocomplete="new-password" minlength="8">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Пароль ещё раз</label>
                        <input type="password" class="form-control form-control-lg"
                               id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Сохранить</button>
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
