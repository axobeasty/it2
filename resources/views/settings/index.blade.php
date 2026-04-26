@extends('layout.settings', ['settingsSection' => 'index'])

@section('page_title', 'Настройки — ' . $settings->title)

@section('settings_heading', 'Настройки системы')
@section('settings_subheading', 'Выберите раздел. Доступ к пунктам зависит от ваших прав.')

@section('settings_content')
    <div class="row g-3">
        <div class="col-md-6 col-xl-4">
            <a href="/settings/general" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 hover-shadow transition-all" style="transition: transform .15s ease, box-shadow .15s ease;">
                    <div class="card-body p-4 d-flex gap-3 align-items-start">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                            <i class="bi bi-gear-fill fs-4"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-semibold text-dark mb-1">Основные</h2>
                            <p class="small text-muted mb-0">Название сайта, техобслуживание, проверка обновлений</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a href="/settings/authenticate" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform .15s ease, box-shadow .15s ease;">
                    <div class="card-body p-4 d-flex gap-3 align-items-start">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                            <i class="bi bi-shield-lock-fill fs-4"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-semibold text-dark mb-1">Аутентификация</h2>
                            <p class="small text-muted mb-0">Пароль, Госуслуги и гибридный режим</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a href="/settings/database" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform .15s ease, box-shadow .15s ease;">
                    <div class="card-body p-4 d-flex gap-3 align-items-start">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                            <i class="bi bi-database-fill fs-4"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-semibold text-dark mb-1">База данных</h2>
                            <p class="small text-muted mb-0">SQLite, удалённый MySQL и мастер настройки</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a href="/settings/email" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform .15s ease, box-shadow .15s ease;">
                    <div class="card-body p-4 d-flex gap-3 align-items-start">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                            <i class="bi bi-envelope-fill fs-4"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-semibold text-dark mb-1">Почта</h2>
                            <p class="small text-muted mb-0">Уведомления по электронной почте</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <style>
        .hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 .75rem 1.75rem rgba(15,23,42,.1) !important; }
    </style>
@endsection
