<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title', $settings->title ?? 'Настройки')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        .settings-body {
            min-height: 100vh;
            background: linear-gradient(165deg, #e8eef8 0%, #f0f4fb 45%, #e4eaf6 100%);
        }
        .settings-shell {
            max-width: 1320px;
        }
        .settings-side-nav .list-group-item {
            border: 0;
            border-radius: 0.5rem !important;
            margin-bottom: 0.25rem;
            padding: 0.65rem 0.9rem;
            color: #334155;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .settings-side-nav .list-group-item:hover {
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
        }
        .settings-side-nav .list-group-item.active {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: #fff;
            font-weight: 600;
        }
        .settings-side-nav .list-group-item.active i {
            color: #fff !important;
        }
        .settings-main-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(15, 23, 42, 0.07);
        }
        .settings-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .settings-field-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.35rem;
        }
    </style>
    @stack('settings_head')
</head>
<body class="settings-body">
@include('layout.nav')

@php
    $section = $settingsSection ?? 'index';
@endphp

<div class="container-fluid settings-shell py-4 px-3 px-lg-4 pb-5 mx-auto">
    <div class="row g-4">
        <div class="col-lg-3 col-xl-3">
            <div class="sticky-lg-top" style="top: 5.5rem; z-index: 100;">
                <div class="rounded-4 bg-white shadow-sm p-3 mb-3">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-light">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                            <i class="bi bi-sliders2 fs-5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold text-dark small text-truncate">Настройки</div>
                            <a href="/" class="text-decoration-none small text-muted">На главную</a>
                        </div>
                    </div>
                    <nav class="list-group list-group-flush settings-side-nav" aria-label="Разделы настроек">
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $section === 'index' ? 'active' : '' }}" href="/settings">
                            <i class="bi bi-grid-1x2-fill {{ $section === 'index' ? '' : 'text-primary' }}"></i>
                            Обзор
                        </a>
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $section === 'general' ? 'active' : '' }}" href="/settings/general">
                            <i class="bi bi-gear-fill {{ $section === 'general' ? '' : 'text-primary' }}"></i>
                            Основные
                        </a>
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $section === 'authenticate' ? 'active' : '' }}" href="/settings/authenticate">
                            <i class="bi bi-shield-lock-fill {{ $section === 'authenticate' ? '' : 'text-primary' }}"></i>
                            Аутентификация
                        </a>
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $section === 'database' ? 'active' : '' }}" href="/settings/database">
                            <i class="bi bi-database-fill {{ $section === 'database' ? '' : 'text-primary' }}"></i>
                            База данных
                        </a>
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $section === 'email' ? 'active' : '' }}" href="/settings/email">
                            <i class="bi bi-envelope-fill {{ $section === 'email' ? '' : 'text-primary' }}"></i>
                            Почта
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-xl-9">
            <div class="card settings-main-card">
                <div class="card-body p-4 p-lg-5">
                    <header class="mb-4 pb-3 border-bottom border-light">
                        <h1 class="h3 fw-semibold text-dark mb-1">@yield('settings_heading')</h1>
                        @hasSection('settings_subheading')
                            <p class="text-muted mb-0 small">@yield('settings_subheading')</p>
                        @endif
                    </header>
                    @yield('settings_content')
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
@stack('settings_scripts')
{!! Toastr::message() !!}
</body>
</html>
