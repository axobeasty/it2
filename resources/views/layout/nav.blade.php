<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
@php
    extract(\App\Support\MenuVisibility::flags($user), EXTR_SKIP);
@endphp
<nav class="navbar navbar-expand-lg sticky-top bg-gradient shadow-sm navbar-icon-only">
    <div class="container-fluid px-2 px-sm-3 px-lg-4">
        <button class="btn btn-light border rounded-3 py-2 px-2 shadow-sm d-lg-none flex-shrink-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarNav" aria-controls="appSidebarNav" aria-label="Меню разделов">
            <i class="bi bi-layout-sidebar-inset-reverse fs-5 text-primary"></i>
        </button>
        <a href="/" class="navbar-brand d-flex align-items-center gap-2 min-w-0 me-auto me-lg-0" style="max-width: min(100%, calc(100vw - 7.5rem));">
            <div class="p-2 bg-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                <img src="{{ asset('imgs/logo_white.png') }}" width="32" height="32" alt="">
            </div>
            <span class="fw-bold text-primary fs-5 text-truncate d-inline-block" style="letter-spacing: -0.5px;">{{ $settings->title }}</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @if($canDashboard)
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom nav-tooltip px-3 py-2 rounded-3" href="/dashboard" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Главная">
                            <i class="bi bi-house-door me-2 opacity-75"></i><span class="nav-text">Главная</span>
                        </a>
                    </li>
                @endif

                @if($canOrdersMy || $canOrdersAdmin)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Заявки">
                        <i class="bi bi-card-checklist me-2 opacity-75"></i><span class="nav-text">Заявки</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canOrdersMy)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/orders/my" title="Мои заявки"><i class="bi bi-list-task me-2 text-primary"></i><span class="dropdown-item-label">Мои заявки</span></a></li>
                        @endif
                        @if($canOrdersAdmin)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/orders/administration" title="Управление заявками"><i class="bi bi-gear-fill me-2 text-primary"></i><span class="dropdown-item-label">Управление</span></a></li>
                            <li><a class="dropdown-item rounded-2 mx-1" href="/orders/categories" title="Категории заявок"><i class="bi bi-tags-fill me-2 text-primary"></i><span class="dropdown-item-label">Категории</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canPasswords)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Инструменты">
                        <i class="bi bi-tools me-2 opacity-75"></i><span class="nav-text">Инструменты</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        <li><a class="dropdown-item rounded-2 mx-1" href="/passwords" title="Менеджер паролей"><i class="bi bi-shield-lock-fill me-2 text-primary"></i><span class="dropdown-item-label">Менеджер паролей</span></a></li>
                    </ul>
                </li>
                @endif

                @if($canInventoryMy || $canInventoryAdmin)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Инвентарь">
                        <i class="bi bi-box-seam me-2 opacity-75"></i><span class="nav-text">Инвентарь</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canInventoryMy)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/inv" title="Мой инвентарь"><i class="bi bi-inbox-fill me-2 text-primary"></i><span class="dropdown-item-label">Мой инвентарь</span></a></li>
                        @endif
                        @if($canInventoryAdmin)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/inv/departments/manage" title="Структурные подразделения"><i class="bi bi-houses-fill me-2 text-primary"></i><span class="dropdown-item-label">Структурные подразделения</span></a></li>
                            <li><a class="dropdown-item rounded-2 mx-1" href="/inv/manage" title="Управление инвентарём"><i class="bi bi-wrench-adjustable me-2 text-primary"></i><span class="dropdown-item-label">Управление</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canStudentTests || $canTestsAdmin || $canTestsStats)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Тестирование">
                        <i class="bi bi-ui-checks me-2 opacity-75"></i><span class="nav-text">Тестирование</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canStudentTests)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/tests" title="Тесты группы"><i class="bi bi-list-check me-2 text-primary"></i><span class="dropdown-item-label">Тесты группы</span></a></li>
                        @endif
                        @if($canTestsAdmin)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/tests/admin" title="Администрирование тестов"><i class="bi bi-ui-checks-grid me-2 text-primary"></i><span class="dropdown-item-label">Администрирование тестов</span></a></li>
                        @endif
                        @if($canTestsStats)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/tests/stats" title="Статистика тестов"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i><span class="dropdown-item-label">Статистика тестов</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canEmployees || $canRoles || $canGroups)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Сотрудники">
                        <i class="bi bi-people me-2 opacity-75"></i><span class="nav-text">Сотрудники</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canEmployees)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/employees" title="Управление пользователями"><i class="bi bi-people-fill me-2 text-primary"></i><span class="dropdown-item-label">Управление пользователями</span></a></li>
                        @endif
                        @if($canRoles)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/roles" title="Управление ролями"><i class="bi bi-person-fill-gear me-2 text-primary"></i><span class="dropdown-item-label">Управление ролями</span></a></li>
                        @endif
                        @if($canGroups)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/groups" title="Управление группами"><i class="bi bi-diagram-3-fill me-2 text-primary"></i><span class="dropdown-item-label">Управление группами</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canFaculties || $canChairs)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Образование">
                        <i class="bi bi-mortarboard me-2 opacity-75"></i><span class="nav-text">Образование</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canFaculties)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/teachers/faculties" title="Факультеты"><i class="bi bi-mortarboard-fill me-2 text-primary"></i><span class="dropdown-item-label">Факультеты</span></a></li>
                        @endif
                        @if($canChairs)
                            <li><a class="dropdown-item rounded-2 mx-1" href="/teachers/chairs" title="Кафедры"><i class="bi bi-building me-2 text-primary"></i><span class="dropdown-item-label">Кафедры</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canScheduleMy || $canScheduleConstructor || $canScheduleConstructorSettings)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Расписание">
                        <i class="bi bi-calendar-week me-2 opacity-75"></i><span class="nav-text">Расписание</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canScheduleMy)
                            <li><a class="dropdown-item rounded-2 mx-1" href="{{ route('schedule.my') }}" title="Моё расписание"><i class="bi bi-calendar3 me-2 text-primary"></i><span class="dropdown-item-label">Моё расписание</span></a></li>
                        @endif
                        @if($canScheduleConstructor)
                            <li><a class="dropdown-item rounded-2 mx-1" href="{{ route('schedule.constructor') }}" title="Конструктор расписания"><i class="bi bi-calendar-plus me-2 text-primary"></i><span class="dropdown-item-label">Конструктор</span></a></li>
                        @endif
                        @if($canScheduleConstructorSettings)
                            <li><a class="dropdown-item rounded-2 mx-1" href="{{ route('schedule.constructor.settings') }}" title="Настройки расписания"><i class="bi bi-gear-wide-connected me-2 text-primary"></i><span class="dropdown-item-label">Настройки конструктора</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canPortfolioOwn || $canPortfolioTypes || $canPortfolioConfirm)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Портфолио">
                        <i class="bi bi-journal-richtext me-2 opacity-75"></i><span class="nav-text">Портфолио</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        @if($canPortfolioOwn)
                        <li><a class="dropdown-item rounded-2 mx-1" href="/profile/portfolio" title="Моё портфолио"><i class="bi bi-collection me-2 text-primary"></i><span class="dropdown-item-label">Моё портфолио</span></a></li>
                        @endif
                        @if($canPortfolioTypes)
                        <li><a class="dropdown-item rounded-2 mx-1" href="/portfolio/types" title="Типы портфолио"><i class="bi bi-list-nested me-2 text-primary"></i><span class="dropdown-item-label">Типы портфолио</span></a></li>
                        @endif
                        @if($canPortfolioConfirm)
                        <li><a class="dropdown-item rounded-2 mx-1" href="{{ route('portfolio.confirm') }}" title="Подтверждение портфолио"><i class="bi bi-check-all me-2 text-primary"></i><span class="dropdown-item-label">Подтверждение портфолио</span></a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($canSettings)
                <li class="nav-item dropdown"
                    onmouseenter="showDropdown(this)"
                    onmouseleave="hideDropdown(this)">
                    <a class="nav-link nav-link-custom dropdown-toggle px-3 py-2 rounded-3" href="#" role="button" title="Настройки">
                        <i class="bi bi-sliders me-2 opacity-75"></i><span class="nav-text">Настройки</span>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        <li><a class="dropdown-item rounded-2 mx-1" href="/settings/general" title="Основные настройки"><i class="bi bi-sliders me-2 text-primary"></i><span class="dropdown-item-label">Основные</span></a></li>
                        <li><a class="dropdown-item rounded-2 mx-1" href="/settings/authenticate" title="Аутентификация"><i class="bi bi-lock-fill me-2 text-primary"></i><span class="dropdown-item-label">Аутентификация</span></a></li>
                        <li><a class="dropdown-item rounded-2 mx-1" href="/settings/email" title="Настройки почты"><i class="bi bi-envelope-fill me-2 text-primary"></i><span class="dropdown-item-label">Настройки почты</span></a></li>
                    </ul>
                </li>
                @endif
            </ul>

            <div class="d-flex align-items-center gap-2 ms-lg-3 flex-wrap">
                @if($canDashboard)
                    <a href="/profile" class="nav-link nav-link-custom px-3 py-2 rounded-3 small" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $user->fio ?? 'Профиль' }}">
                        <i class="bi bi-person-circle fs-5 opacity-75"></i>
                    </a>
                @endif
                <a href="/logout" class="btn btn-sm p-0 fw-medium" title="Выход">
                    <span class="nav-link nav-link-custom px-3 py-2 rounded-3 d-inline-block mb-0"><i class="bi bi-box-arrow-right fs-5 opacity-75"></i></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    function showDropdown(element) {
        if (window.innerWidth < 992) {
            return;
        }
        if (element._navHideTimer) {
            clearTimeout(element._navHideTimer);
            element._navHideTimer = null;
        }
        const menu = element.querySelector('.dropdown-menu');
        const toggle = element.querySelector('.dropdown-toggle');
        if (menu) {
            menu.classList.add('show');
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    }

    function hideDropdown(element) {
        if (window.innerWidth < 992) {
            return;
        }
        if (element._navHideTimer) {
            clearTimeout(element._navHideTimer);
        }
        element._navHideTimer = setTimeout(function () {
            element._navHideTimer = null;
            const menu = element.querySelector('.dropdown-menu');
            const toggle = element.querySelector('.dropdown-toggle');
            if (menu) {
                menu.classList.remove('show');
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        }, 220);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var sidebarBtn = document.querySelector('[data-bs-target="#appSidebarNav"]');
        if (sidebarBtn && !document.getElementById('appSidebarNav')) {
            sidebarBtn.classList.add('d-none');
        }
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        }
    });
</script>

<style>
    .navbar.bg-gradient {
        background: linear-gradient(135deg, #ffffff 0%, #f4f7ff 50%, #f8f9fc 100%) !important;
        border-bottom: 1px solid rgba(13, 110, 253, 0.08);
    }

    .nav-link-custom {
        color: #495057 !important;
        font-weight: 500;
        transition: all 0.25s ease;
    }

    .nav-link-custom:hover,
    .nav-link-custom:focus {
        color: #0d6efd !important;
        background: rgba(13, 110, 253, 0.08);
    }

    .navbar .dropdown-menu {
        margin-top: 0;
        min-width: 260px;
        padding: 0.5rem;
        border: 1px solid rgba(13, 110, 253, 0.12) !important;
        border-radius: 14px !important;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        box-shadow:
            0 16px 38px rgba(15, 23, 42, 0.13),
            0 3px 10px rgba(15, 23, 42, 0.07);
        transform-origin: top left;
        animation: dropdownFadeIn 0.18s ease-out;
    }

    .navbar .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.56rem 0.7rem;
        border-radius: 10px;
        color: #344054;
        font-weight: 500;
        transition: all 0.18s ease;
    }

    .navbar .dropdown-item i {
        width: 1.2rem;
        text-align: center;
        font-size: 1rem;
        opacity: 0.9;
    }

    .navbar .dropdown-item:hover,
    .navbar .dropdown-item:focus {
        color: #0d6efd;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.11), rgba(13, 110, 253, 0.05));
        transform: translateX(2px);
    }

    .navbar .dropdown-item:active {
        transform: translateX(1px) scale(0.995);
    }

    .navbar .dropdown-item + .dropdown-item {
        margin-top: 0.22rem;
    }

    .navbar .dropdown-toggle {
        position: relative;
    }

    .navbar .dropdown-toggle::after {
        margin-left: 0.4rem;
        transition: transform 0.2s ease;
    }

    .navbar .dropdown.show > .dropdown-toggle::after,
    .navbar .dropdown:hover > .dropdown-toggle::after {
        transform: rotate(180deg);
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(6px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Невидимый «мост» над меню: без него курсор в зазоре между триггером и ul не попадает в li и срабатывает mouseleave */
    @media (min-width: 992px) {
        .navbar .nav-item.dropdown {
            position: relative;
        }
        .navbar .nav-item.dropdown > .dropdown-menu::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: -10px;
            height: 10px;
        }
        .navbar .dropdown:hover > .dropdown-menu {
            display: block;
        }
    }

    @media (max-width: 991.98px) {
        .dropdown-menu {
            margin-top: 0.5rem !important;
            min-width: 100%;
            border-radius: 12px !important;
            backdrop-filter: none;
        }
    }

    .navbar.navbar-icon-only .nav-link-custom .nav-text {
        display: none;
    }
    .navbar.navbar-icon-only .nav-link-custom i {
        margin-right: 0 !important;
        font-size: 1.25rem;
    }
    .navbar.navbar-icon-only .nav-link-custom.dropdown-toggle::after {
        display: none;
    }
    .navbar.navbar-icon-only .navbar-nav {
        gap: 0.7rem;
    }
    .navbar.navbar-icon-only .nav-link-custom {
        padding-left: 0.9rem !important;
        padding-right: 0.9rem !important;
    }
    .navbar.navbar-icon-only .dropdown-item i {
        margin-right: 0 !important;
    }
    .navbar.navbar-icon-only .dropdown-menu {
        min-width: auto;
    }
</style>
