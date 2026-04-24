<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Общее меню</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    @php
        $choiceUser = session('user');
        $choiceShowPortfolio = $choiceUser && $choiceUser->canAccessPage('portfolio');
    @endphp
    <style>
        body {
            background: linear-gradient(160deg, #eef3ff 0%, #e9f0f8 50%, #f7f9ff 100%);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
        }
        .menu-wrapper {
            max-width: 1180px;
            margin: 0 auto;
        }
        .menu-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.08);
            overflow: hidden;
        }
        .menu-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            padding: 24px;
        }
        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 12px;
            padding: 8px 10px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            color: #0d6efd;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            font-size: .85rem;
        }
        .user-name {
            margin: 0;
            font-weight: 600;
            font-size: .92rem;
            line-height: 1.1;
        }
        .user-role {
            margin: 0;
            font-size: .78rem;
            opacity: .9;
            line-height: 1.1;
        }
        .user-logout {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border: 1px solid rgba(255,255,255,.45);
            text-decoration: none;
            transition: all .2s ease;
        }
        .user-logout:hover {
            background: rgba(255,255,255,.2);
            color: #fff;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
            padding: 20px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #1f2937;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            transition: all .2s ease;
            min-height: 72px;
        }
        .menu-item:hover {
            transform: translateY(-2px);
            border-color: #b6d4fe;
            box-shadow: 0 8px 18px rgba(13, 110, 253, 0.12);
            color: #0d6efd;
        }
        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e7f1ff;
            color: #0d6efd;
            font-size: 1.2rem;
            flex: 0 0 40px;
        }
        .menu-title {
            font-weight: 600;
            margin: 0;
            font-size: .98rem;
        }
        .menu-sub {
            margin: 0;
            font-size: .82rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="container px-3 min-vh-100 d-flex align-items-center">
    <div class="menu-wrapper">
        <div class="menu-card bg-white">
            <div class="menu-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">Добро пожаловать</h4>
                        <p class="mb-0 opacity-75">Выберите нужный раздел</p>
                    </div>
                    @php
                        $nameParts = explode(' ', trim((string) $user->fio));
                        $initials = '';
                        if (isset($nameParts[0]) && $nameParts[0] !== '') {
                            $initials .= mb_substr($nameParts[0], 0, 1, 'UTF-8');
                        }
                        if (isset($nameParts[1]) && $nameParts[1] !== '') {
                            $initials .= mb_substr($nameParts[1], 0, 1, 'UTF-8');
                        }
                        $roleName = optional(\App\Models\Roles::find($user->role_id))->name ?? 'Сотрудник';
                    @endphp
                    <div class="user-chip">
                        <div class="user-avatar">{{ strtoupper($initials) }}</div>
                        <div>
                            <p class="user-name">{{ $user->fio }}</p>
                            <p class="user-role">{{ $roleName }}</p>
                        </div>
                        <a href="/logout" class="user-logout ms-1" title="Выйти из аккаунта">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="menu-grid">
                @if($user->canAccessPage('dashboard') && ! $user->canAccessPage('settings'))
                    <a href="/dashboard" class="menu-item">
                        <span class="menu-icon"><i class="bi bi-speedometer2"></i></span>
                        <span>
                            <p class="menu-title">Администратору</p>
                            <p class="menu-sub">Переход в dashboard</p>
                        </span>
                    </a>
                @endif

                <a href="/teachers" class="menu-item">
                    <span class="menu-icon"><i class="bi bi-person-workspace"></i></span>
                    <span>
                        <p class="menu-title">Учителю</p>
                        <p class="menu-sub">Рабочие разделы</p>
                    </span>
                </a>

                <a href="/students" class="menu-item">
                    <span class="menu-icon"><i class="bi bi-mortarboard"></i></span>
                    <span>
                        <p class="menu-title">Студенту</p>
                        <p class="menu-sub">Учебные разделы</p>
                    </span>
                </a>

                @if($choiceShowPortfolio)
                <a href="/profile/portfolio" class="menu-item">
                    <span class="menu-icon"><i class="bi bi-collection"></i></span>
                    <span>
                        <p class="menu-title">Портфолио</p>
                        <p class="menu-sub">Ваши достижения</p>
                    </span>
                </a>
                @endif

                <a href="/profile" class="menu-item">
                    <span class="menu-icon"><i class="bi bi-person-circle"></i></span>
                    <span>
                        <p class="menu-title">Профиль</p>
                        <p class="menu-sub">Личные данные</p>
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
