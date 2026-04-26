<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Управление ролями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        body { background: #eaeff6; }
        .roles-shell {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }
        .role-perms-section .card-header {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }
        /* Модалки прав роли: на весь экран, скролл только у тела формы */
        .role-perms-fullscreen-modal .modal-dialog.modal-fullscreen {
            height: 100%;
        }
        .role-perms-fullscreen-modal .modal-content {
            height: 100%;
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .role-perms-fullscreen-modal .modal-header {
            flex-shrink: 0;
        }
        .role-perms-fullscreen-modal .role-perms-form {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .role-perms-fullscreen-modal .role-perms-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .role-perms-fullscreen-modal .modal-footer {
            flex-shrink: 0;
        }
        .roles-toolbar .form-control {
            max-width: 18rem;
        }
        .perm-toggle {
            margin: 0;
        }
        /* Невидимый чекбокс поверх «кнопки», чтобы клик всегда переключал значение и уходил в POST */
        .perm-toggle__input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
            -webkit-appearance: none;
            appearance: none;
        }
        .perm-toggle__face {
            cursor: pointer;
            user-select: none;
            position: relative;
            z-index: 1;
            pointer-events: none;
            min-height: 2.75rem;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            border-color: #94a3b8 !important;
            background-color: #fff;
            color: #334155;
            box-sizing: border-box;
        }
        .perm-toggle__face:hover {
            border-color: #64748b !important;
            background-color: #f8fafc;
        }
        .perm-toggle__input:focus-visible + .perm-toggle__face {
            outline: 2px solid rgba(13, 110, 253, 0.45);
            outline-offset: 2px;
        }
        .perm-toggle__input:focus {
            outline: none;
        }
        .perm-toggle__input:checked + .perm-toggle__face {
            background-color: #f4f9ff;
            border-color: #0d6efd !important;
            color: #0f172a;
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.12);
        }
        .perm-toggle__input:checked + .perm-toggle__face:hover {
            background-color: #e8f2ff;
            border-color: #0b5ed7 !important;
        }
        .perm-toggle__input:checked + .perm-toggle__face .perm-toggle__text {
            font-weight: 600;
        }
        .perm-toggle__icon {
            width: 1.35rem;
            height: 1.35rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.7rem;
            flex-shrink: 0;
            border: 2px dashed #cbd5e1;
            background: transparent;
            color: transparent;
        }
        .perm-toggle__input:checked + .perm-toggle__face .perm-toggle__icon {
            border: 2px solid #0d6efd;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        .roles-empty-hint {
            border: 1px dashed rgba(15, 23, 42, 0.12);
            border-radius: 12px;
            background: #f8fafc;
        }
        .role-tile {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
            min-height: 4.5rem;
        }
        .role-tile:hover {
            border-color: rgba(13, 110, 253, 0.28);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.1);
            transform: translateY(-2px);
        }
        .role-tile:focus-within {
            border-color: rgba(13, 110, 253, 0.45);
        }
        .role-tile__open {
            background: linear-gradient(125deg, #f8fafc 0%, #eef2ff 45%, #f1f5f9 100%);
            color: #0f172a;
        }
        .role-tile--system .role-tile__open {
            background: linear-gradient(125deg, #f1f5f9 0%, #e8ecf1 50%, #f8fafc 100%);
        }
        .role-tile--custom .role-tile__open {
            background: linear-gradient(125deg, #eff6ff 0%, #e0e7ff 40%, #f5f3ff 100%);
        }
        .role-tile__open:hover {
            filter: brightness(1.02);
        }
        .role-tile__open:active {
            filter: brightness(0.98);
        }
        .role-tile__glyph {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .role-tile--system .role-tile__glyph {
            background: rgba(100, 116, 139, 0.15);
            color: #475569;
        }
        .role-tile--custom .role-tile__glyph {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
        }
        .role-tile__chevron {
            opacity: 0.45;
        }
        .role-tile__menu .dropdown-toggle::after {
            display: none;
        }
        .role-tile__menu .btn {
            color: #64748b;
        }
        .role-tile__menu .btn:hover,
        .role-tile__menu .btn:focus {
            background: rgba(15, 23, 42, 0.05);
            color: #334155;
        }
    </style>
</head>
<body>
@include('layout.nav')
<div class="container py-4">
    <div class="roles-shell p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-0">Управление ролями</h4>
                <p class="text-muted small mb-0 mt-1">Нажмите на карточку роли, чтобы открыть настройки. В окне права переключаются по клику: серая обводка — выкл., синяя — вкл.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 roles-toolbar">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" class="form-control border-start-0" id="roles-filter" placeholder="Поиск по названию…" autocomplete="off" aria-label="Фильтр ролей">
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    <i class="bi bi-plus-lg me-1"></i>Добавить роль
                </button>
            </div>
        </div>
        <hr class="my-3">

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="roles-grid">
            @foreach($roles as $role)
                <div class="col role-card-wrap" data-role-name="{{ mb_strtolower($role->name) }}">
                    <div class="role-tile role-tile--{{ $role->is_system ? 'system' : 'custom' }} d-flex bg-white">
                        <button type="button" class="role-tile__open btn flex-grow-1 d-flex align-items-center gap-3 border-0 rounded-0 text-start py-3 ps-3 pe-2" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}" aria-label="Настроить роль {{ $role->name }}">
                            <span class="role-tile__glyph" aria-hidden="true">
                                @if($role->is_system)
                                    <i class="bi bi-shield-lock"></i>
                                @else
                                    <i class="bi bi-person-badge"></i>
                                @endif
                            </span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="fw-semibold d-block text-truncate">{{ $role->name }}</span>
                                <span class="small text-muted">
                                    @if($role->is_system)
                                        Системная роль
                                    @else
                                        Кастомная роль
                                    @endif
                                </span>
                            </span>
                            <i class="bi bi-chevron-right role-tile__chevron flex-shrink-0 me-1" aria-hidden="true"></i>
                        </button>
                        <div class="role-tile__menu border-start bg-white flex-shrink-0">
                            <div class="dropdown h-100">
                                <button class="btn btn-light border-0 rounded-0 h-100 px-3 d-flex align-items-center" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Действия для роли {{ $role->name }}">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}">
                                            <i class="bi bi-sliders me-2 text-primary"></i>Настроить
                                        </button>
                                    </li>
                                    @if(! $role->is_system)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="/roles/{{ $role->id }}/delete" onclick="return confirm('Удалить роль «{{ $role->name }}»?');">
                                                <i class="bi bi-trash me-2"></i>Удалить
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="roles-filter-empty" class="roles-empty-hint text-center text-muted small py-4 mt-3 d-none">
            Ничего не найдено. Попробуйте другой запрос.
        </div>
    </div>
</div>

@foreach($roles as $role)
    <div class="modal fade role-perms-fullscreen-modal" id="editRole{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen m-0">
            <div class="modal-content rounded-0 border-0">
                <div class="modal-header py-3 shadow-sm">
                    <h2 class="modal-title fs-5 mb-0">Редактирование: {{ $role->name }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form class="role-perms-form" action="/roles/{{ $role->id }}/edit" method="post">
                    @csrf
                    <div class="modal-body role-perms-modal-body py-3 px-3 px-md-4">
                        <div class="container-fluid" style="max-width: 1400px;">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Название роли</label>
                                @if($role->is_system)
                                    <input type="text" class="form-control bg-light" value="{{ $role->name }}" readonly aria-readonly="true">
                                    <div class="form-text">Название системной роли нельзя изменить.</div>
                                @else
                                    <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                @endif
                            </div>
                            <label class="form-label fw-semibold d-block mb-2">Доступ к разделам</label>
                            <p class="text-muted small mb-3">Серая рамка — доступ выключен, синяя — включён. Нажмите строку, чтобы переключить.</p>
                            @include('Employees.partials.role_permissions_form', [
                                'groupedPages' => $groupedPages,
                                'idPrefix' => 'r'.$role->id,
                                'editableRole' => $role,
                            ])
                        </div>
                    </div>
                    <div class="modal-footer py-3 border-top bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade role-perms-fullscreen-modal" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen m-0">
        <div class="modal-content rounded-0 border-0">
            <div class="modal-header py-3 shadow-sm">
                <h2 class="modal-title fs-5 mb-0">Новая кастомная роль</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form class="role-perms-form" action="/roles/create" method="post">
                @csrf
                <div class="modal-body role-perms-modal-body py-3 px-3 px-md-4">
                    <div class="container-fluid" style="max-width: 1400px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Название роли</label>
                            <input type="text" name="name" class="form-control" required placeholder="Например, Куратор группы">
                        </div>
                        <label class="form-label fw-semibold d-block mb-2">Доступ к разделам</label>
                        <p class="text-muted small mb-3">Серая рамка — доступ выключен, синяя — включён. Нажмите строку, чтобы переключить.</p>
                        @include('Employees.partials.role_permissions_form', [
                            'groupedPages' => $groupedPages,
                            'idPrefix' => 'create',
                            'editableRole' => null,
                        ])
                    </div>
                </div>
                <div class="modal-footer py-3 border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать роль</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
<script>
(function () {
    var input = document.getElementById('roles-filter');
    var grid = document.getElementById('roles-grid');
    var emptyHint = document.getElementById('roles-filter-empty');
    if (!input || !grid) return;
    var wraps = grid.querySelectorAll('.role-card-wrap');
    function apply() {
        var q = (input.value || '').trim().toLowerCase();
        var visible = 0;
        wraps.forEach(function (el) {
            var name = el.getAttribute('data-role-name') || '';
            var show = !q || name.indexOf(q) !== -1;
            el.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        emptyHint.classList.toggle('d-none', visible !== 0);
    }
    input.addEventListener('input', apply);
    input.addEventListener('search', apply);
})();
</script>
{!! Toastr::message() !!}
</body>
</html>
