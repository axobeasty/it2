<style>
    /*
     * Sidebar: каркас и заголовки как на дашборде; пункты — спокойное выделение без теней и полосок.
     */
    .app-sidebar {
        font-family: 'Segoe UI', sans-serif;
        background: #ffffff;
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        max-height: calc(100dvh - 5rem);
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .app-sidebar .sidebar-nav {
        padding: 1.25rem 1rem 1.5rem;
    }

    /* Как .nav-title на дашборде */
    .app-sidebar .sidebar-section-title {
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        color: #6c757d !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin: 1.35rem 0 0.4rem 0 !important;
        padding: 0.5rem 0.75rem 0.15rem !important;
        border: none !important;
        background: none !important;
    }

    .app-sidebar .sidebar-nav > .mb-4:first-child .sidebar-section-title {
        margin-top: 0.35rem !important;
    }

    .app-sidebar .nav.flex-column.gap-1 {
        gap: 0.35rem !important;
    }

    .app-sidebar .sidebar-link {
        color: #0d6efd !important;
        font-weight: 500;
        font-size: 0.875rem;
        line-height: 1.4;
        padding: 0.65rem 0.75rem !important;
        margin: 0 0 0.1rem 0 !important;
        border-radius: 8px !important;
        border: none !important;
        background: transparent !important;
        transition: background-color 0.15s ease, color 0.15s ease;
        word-break: break-word;
    }

    .app-sidebar .sidebar-link:hover,
    .app-sidebar .sidebar-link:focus-visible {
        color: #0a58ca !important;
        background: rgba(0, 0, 0, 0.045) !important;
    }

    .app-sidebar .sidebar-link:focus-visible {
        outline: 2px solid rgba(13, 110, 253, 0.28);
        outline-offset: 1px;
    }

    .app-sidebar .sidebar-link i {
        width: 1.35rem;
        text-align: center;
        font-size: 1.05rem;
        flex-shrink: 0;
        color: #0d6efd !important;
        transition: color 0.15s ease;
    }

    .app-sidebar .sidebar-link:hover i,
    .app-sidebar .sidebar-link:focus-visible i {
        color: #0a58ca !important;
    }

    /* Мобильный drawer: белая панель как у уведомлений */
    .app-sidebar-offcanvas.offcanvas {
        --bs-offcanvas-width: min(20rem, 92vw);
        --bs-offcanvas-bg: #ffffff;
        background-color: #ffffff !important;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.07);
    }

    .app-sidebar-offcanvas .offcanvas-body {
        background: transparent;
    }

    /* Заголовок шторки — как «Уведомления» (.header-title) */
    .app-sidebar-drawer-header {
        background: #ffffff !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
    }

    .app-sidebar-drawer-header .offcanvas-title {
        font-weight: 600;
        color: #000;
        font-size: 1.25rem;
        letter-spacing: -0.02em;
    }

    @media (min-width: 992px) {
        .sidebar-offcanvas-column {
            align-self: stretch;
        }
        .app-sidebar-offcanvas.offcanvas-lg {
            height: 100%;
            background: transparent !important;
            box-shadow: none !important;
        }
        .app-sidebar-offcanvas.offcanvas-lg .offcanvas-body {
            max-height: none;
            overflow: visible;
            padding: 0 !important;
        }
    }

    @media (max-width: 991.98px) {
        .app-sidebar-offcanvas .offcanvas-body {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .app-sidebar {
            max-height: none;
            border-radius: 0;
            box-shadow: none;
            background: transparent;
        }
        .app-sidebar .sidebar-nav {
            padding: 1rem 0.85rem 1.25rem;
        }
        .app-sidebar .sidebar-link {
            min-height: 2.85rem;
        }
    }

    .sidebar-icon-adaptive {
        container-type: inline-size;
        container-name: sidebar;
    }

    @container sidebar (max-width: 13.5rem) {
        .sidebar-icon-adaptive .sidebar-section-title {
            display: none;
        }
        .sidebar-icon-adaptive .sidebar-link {
            justify-content: center;
            padding: 0.65rem 0.5rem !important;
        }
        .sidebar-icon-adaptive .sidebar-link i {
            margin-right: 0 !important;
        }
        .sidebar-icon-adaptive .sidebar-link .sidebar-link-text {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    }
</style>
@php
    extract(\App\Support\MenuVisibility::flags($user), EXTR_SKIP);
@endphp
<div class="sidebar app-sidebar sidebar-icon-adaptive d-flex flex-column h-100">
    <nav class="sidebar-nav flex-grow-1 w-100">
        <div class="mb-4 pt-3">
            @if($canDashboard)
            <ul class="nav flex-column gap-1 pb-3">
                <li>
                    <a href="/dashboard" title="Главная" class="nav-link sidebar-link d-flex align-items-center">
                        <i class="bi bi-house-fill me-2 text-primary"></i>
                        <span class="fw-medium sidebar-link-text">Главная</span>
                    </a>
                </li>
            </ul>
            @endif
            @if($canOrdersMy || $canOrdersAdmin)
            <h6 class="sidebar-section-title">
                Заявки
            </h6>
            <ul class="nav flex-column gap-1">
                @if($canOrdersMy)
                <li>
                    <a href="/orders/my" title="Мои заявки" class="nav-link sidebar-link d-flex align-items-center">
                        <i class="bi bi-list-task me-2 text-primary"></i>
                        <span class="fw-medium sidebar-link-text">Мои заявки</span>
                    </a>
                </li>
                @endif
                @if($canOrdersAdmin)
                    <li>
                        <a href="/orders/administration" title="Управление заявками" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-gear-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Управление</span>
                        </a>
                    </li>
                    <li>
                        <a href="/orders/categories" title="Категории заявок" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-tags-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Категории</span>
                        </a>
                    </li>
                @endif
            </ul>
            @endif
        </div>

        @if($canPasswords)
        <div class="mb-4">
            <h6 class="sidebar-section-title">
                Инструменты
            </h6>
            <ul class="nav flex-column gap-1">
                <li>
                    <a href="/passwords" title="Менеджер паролей" class="nav-link sidebar-link d-flex align-items-center">
                        <i class="bi bi-shield-lock-fill me-2 text-primary"></i>
                        <span class="fw-medium sidebar-link-text">Менеджер паролей</span>
                    </a>
                </li>
            </ul>
        </div>
        @endif

        @if($canInventoryMy || $canInventoryAdmin)
        <div class="mb-4">
            <h6 class="sidebar-section-title">
                Инвентарь
            </h6>
            <ul class="nav flex-column gap-1">
                @if($canInventoryMy)
                <li>
                    <a href="/inv" title="Мой инвентарь" class="nav-link sidebar-link d-flex align-items-center">
                        <i class="bi bi-inbox-fill me-2 text-primary"></i>
                        <span class="fw-medium sidebar-link-text">Мой инвентарь</span>
                    </a>
                </li>
                @endif
                @if($canInventoryAdmin)
                    <li>
                        <a href="/inv/departments/manage" title="Структурные подразделения" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-houses-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Структурные подразделения</span>
                        </a>
                    </li>
                    <li>
                        <a href="/inv/manage" title="Управление инвентарём" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-tools me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Управление</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        @endif

        @if($canStudentTests || $canTestsAdmin || $canTestsStats)
            <div class="mb-4">
                <h6 class="sidebar-section-title">
                    Тестирование
                </h6>
                <ul class="nav flex-column gap-1">
                    @if($canStudentTests)
                        <li>
                            <a href="/tests" title="Тесты группы" class="nav-link sidebar-link d-flex align-items-center">
                                <i class="bi bi-list-check me-2 text-primary"></i>
                                <span class="fw-medium sidebar-link-text">Тесты группы</span>
                            </a>
                        </li>
                    @endif
                    @if($canTestsAdmin)
                        <li>
                            <a href="/tests/admin" title="Администрирование тестов" class="nav-link sidebar-link d-flex align-items-center">
                                <i class="bi bi-ui-checks-grid me-2 text-primary"></i>
                                <span class="fw-medium sidebar-link-text">Администрирование тестов</span>
                            </a>
                        </li>
                    @endif
                    @if($canTestsStats)
                        <li>
                            <a href="/tests/stats" title="Статистика тестов" class="nav-link sidebar-link d-flex align-items-center">
                                <i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>
                                <span class="fw-medium sidebar-link-text">Статистика тестов</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        @if($canEmployees || $canRoles || $canGroups)
            <div class="mb-4">
                <h6 class="sidebar-section-title">
                    Сотрудники
                </h6>
                <ul class="nav flex-column gap-1">
                    @if($canEmployees)
                    <li>
                        <a href="/employees" title="Управление пользователями" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-people-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Управление пользователями</span>
                        </a>
                    </li>
                    @endif
                    @if($canRoles)
                    <li>
                        <a href="/roles" title="Управление ролями" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-person-fill-gear me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Управление ролями</span>
                        </a>
                    </li>
                    @endif
                    @if($canGroups)
                    <li>
                        <a href="/groups" title="Управление группами" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-diagram-3-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Управление группами</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        @endif

        @if($canFaculties || $canChairs)
            <div class="mb-4">
                <h6 class="sidebar-section-title">
                    Образовательный процесс
                </h6>
                <ul class="nav flex-column gap-1">
                    @if($canFaculties)
                    <li>
                        <a href="/teachers/faculties" title="Факультеты" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-mortarboard-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Факультеты</span>
                        </a>
                    </li>
                    @endif
                    @if($canChairs)
                    <li>
                        <a href="/teachers/chairs" title="Кафедры" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-building me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Кафедры</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        @endif

        @if($canScheduleMy || $canScheduleConstructor || $canScheduleConstructorSettings)
            <div class="mb-4">
                <h6 class="sidebar-section-title">
                    Расписание
                </h6>
                <ul class="nav flex-column gap-1">
                    @if($canScheduleMy)
                    <li>
                        <a href="{{ route('schedule.my') }}" title="Моё расписание" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-calendar3 me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Моё расписание</span>
                        </a>
                    </li>
                    @endif
                    @if($canScheduleConstructor)
                    <li>
                        <a href="{{ route('schedule.constructor') }}" title="Конструктор расписания" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-calendar-plus me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Конструктор</span>
                        </a>
                    </li>
                    @endif
                    @if($canScheduleConstructorSettings)
                    <li>
                        <a href="{{ route('schedule.constructor.settings') }}" title="Настройки конструктора расписания" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-gear-wide-connected me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Настройки конструктора</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        @endif

        @if($canPortfolioOwn || $canPortfolioTypes || $canPortfolioConfirm)
            <div class="mb-4">
                <h6 class="sidebar-section-title">
                    Портфолио
                </h6>
                <ul class="nav flex-column gap-1">
                    @if($canPortfolioOwn)
                    <li>
                        <a href="/profile/portfolio" title="Моё портфолио" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-collection me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Моё портфолио</span>
                        </a>
                    </li>
                    @endif
                    @if($canPortfolioTypes)
                    <li>
                        <a href="/portfolio/types" title="Типы портфолио" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-list-nested me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Типы портфолио</span>
                        </a>
                    </li>
                    @endif
                    @if($canPortfolioConfirm)
                    <li>
                        <a href="{{ route('portfolio.confirm') }}" title="Подтверждение портфолио" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-check-all me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Подтверждение портфолио</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        @endif

            @if($canSettings)
            <div>
                <h6 class="sidebar-section-title">
                    Настройки
                </h6>
                <ul class="nav flex-column gap-1">
                    <li>
                        <a href="/settings/general" title="Основные настройки" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-sliders me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Основные</span>
                        </a>
                    </li>
                    <li>
                        <a href="/settings/authenticate" title="Аутентификация" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-lock-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Аутентификация</span>
                        </a>
                    </li>
                    <li>
                        <a href="/settings/email" title="Настройки почты" class="nav-link sidebar-link d-flex align-items-center">
                            <i class="bi bi-envelope-fill me-2 text-primary"></i>
                            <span class="fw-medium sidebar-link-text">Настройки почты</span>
                        </a>
                    </li>
                </ul>
            </div>
            @endif
    </nav>
</div>

