<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>{{ $settings->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        body {
            background: #eaeff6;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            margin: 0;
        }

        .left-section {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .left-section h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
        }

        .left-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            text-align: center;
        }

        .logo-left {
            width: 80%;
            max-width: 240px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.15));
        }

        .right-section {
            background: #ffffff;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
            padding: 10px 24px;
            font-weight: 500;
            width: 100%;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0b5ed7, #0a58ca);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .btn-gosuslugi {
            background: linear-gradient(135deg, #2c9f42, #238a35);
            color: white;
            border: none;
            padding: 10px 24px;
            font-weight: 500;
            width: 100%;
            margin-top: 12px;
            transition: all 0.3s;
        }

        .btn-gosuslugi:hover {
            background: linear-gradient(135deg, #238a35, #1e752d);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 159, 66, 0.3);
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .version {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 1rem;
        }

        .form-label {
            font-size: 0.875rem;
            color: #495057;
        }

        .login-stack {
            min-height: 100dvh;
        }

        .login-mobile-hero {
            display: none;
        }

        @media (max-width: 991.98px) {
            body {
                min-height: 100dvh;
            }

            .login-stack .row {
                min-height: 100dvh;
                height: auto !important;
            }

            .login-stack .col-lg-6 {
                height: auto !important;
                overflow: visible !important;
            }

            .login-mobile-hero {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 1.75rem 1.25rem 1rem;
                background: linear-gradient(135deg, #0d6efd, #0a58ca);
                color: #fff;
            }

            .login-mobile-hero img {
                width: min(200px, 55vw);
                height: auto;
                margin-bottom: 1rem;
                filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.15));
            }

            .login-mobile-hero h2 {
                font-size: 1.15rem;
                font-weight: 600;
                margin: 0;
                line-height: 1.35;
            }

            .login-mobile-hero p {
                font-size: 0.9rem;
                opacity: 0.92;
                margin: 0.75rem 0 0;
            }

            .right-section {
                min-height: auto;
                border-radius: 0 !important;
                padding: 1.25rem !important;
                align-items: flex-start !important;
            }

            .left-section.d-none {
                display: none !important;
            }
        }

        @media (min-width: 992px) {
            .container-fluid.login-stack, .login-stack .row, .login-stack .col-lg-6 {
                padding: 0;
                margin: 0;
                height: 100%;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0 h-100 login-stack">
    <div class="row g-0 h-100">
        <div class="col-12 login-mobile-hero d-lg-none">
            <img src="{{ asset('imgs/logo_white.png') }}" alt="" draggable="false">
            <h2>ГАПОУ «КГАМТ имени Л.Б.Васильева»</h2>
            <p>Войдите в систему, чтобы продолжить</p>
        </div>
        <div class="col-lg-6 d-none d-lg-flex p-0">
            <div class="left-section h-100 w-100 ">
                <img src="{{asset('imgs/logo_white.png')}}" alt="Логотип" class="" draggable="false">
                <h2> ГАПОУ «КГАМТ имени Л.Б.Васильева»</h2>
                <p class="pt-5">Для работы с системой необходимо пройти аутентификацию</p>
            </div>
        </div>
        <div class="col-lg-6 p-0">
            <div class="right-section h-100">
                <div class="login-card">

                    <div class="card shadow-sm">
                        @if($settings->is_enabled == 0)
                            <div class="card-body text-center p-5">
                                <div class="maintenance-container"
                                     style="background: linear-gradient(135deg, #fff8e6, #fff1d9); border-radius: 14px; border: 1px solid #ffe5b3; padding: 2rem; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.15);">
                                    <div class="mb-3" style="font-size: 3rem;">
                                        <i class="bi bi-cone-striped" style="color: #fd7e14;"></i>
                                    </div>
                                    <h5 class="mb-2" style="color: #e67700; font-weight: 600;">Сайт на техническом обслуживании</h5>
                                    <p class="mb-3" style="color: #8a6d3b; font-size: 0.95rem; line-height: 1.6;">
                                        {{ $settings->disable_reason ?: 'Система временно недоступна. Ведутся профилактические работы.' }}
                                    </p>
                                    <div class="spinner-border text-warning mb-3" role="status" style="width: 28px; height: 28px;">
                                        <span class="visually-hidden">Загрузка...</span>
                                    </div>
                                    <br>
                                    <small class="text-muted" style="font-size: 0.85rem;">
                                        Пожалуйста, зайдите позже.
                                    </small>
                                </div>
                            </div>
                        @else
                    @switch ($settings->auth_mode)
                            @case (0)
                        <div class="card-body p-4">
                            <form action="/auth" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="login" class="form-label">Логин</label>
                                    <input type="text" class="form-control form-control-lg" id="login" name="login"
                                           placeholder="Введите логин" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password"
                                           placeholder="Введите пароль" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg">Войти</button>
                                <p class="text-center mt-3 mb-0">
                                    <a href="{{ route('password.forgot') }}" class="small">Восстановить пароль</a>
                                </p>
                            </form>
                        </div>
                        @break
                        @case (1)

                        <div class="card-body p-4">
                            <button type="submit" class="btn btn-primary btn-lg">Вход через госулуги</button>
                        </div>
                            @break
                            @case(2)

                                <div class="card-body p-4">
                                    <form action="/auth" method="post">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="login" class="form-label">Логин</label>
                                            <input type="text" class="form-control form-control-lg" id="login" name="login"
                                                   placeholder="Введите логин" required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="password" class="form-label">Пароль</label>
                                            <input type="password" class="form-control form-control-lg" id="password" name="password"
                                                   placeholder="Введите пароль" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-lg">Войти</button>
                                        <p class="text-center mt-3 mb-0">
                                            <a href="{{ route('password.forgot') }}" class="small">Восстановить пароль</a>
                                        </p>
                                    </form>
                                </div>
                                    <p class="text-center">ИЛИ</p>
                                    <div class="card-body p-4">
                                        <button type="submit" class="btn btn-primary btn-lg">Вход через госуслуги</button>
                                    </div>
                                    @break
                                @endswitch
                    </div>
                    @endif
                    <p class="version text-center">Версия приложения: v2.1</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
