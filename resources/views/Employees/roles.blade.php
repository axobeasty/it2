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
        .role-perm-switch .form-check-input {
            width: 2.65rem;
            height: 1.3rem;
            margin-top: 0.15rem;
            cursor: pointer;
        }
        .role-perm-switch .form-check-label {
            cursor: pointer;
            line-height: 1.35;
            font-size: 0.9375rem;
        }
        .role-perms-section .card-header {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }
        .modal-perms-body {
            max-height: min(70vh, 36rem);
            overflow-y: auto;
        }
        .roles-toolbar .form-control {
            max-width: 18rem;
        }
        .role-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .role-card:hover {
            border-color: rgba(13, 110, 253, 0.25);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .role-card.role-card--system {
            border-left: 4px solid #64748b;
        }
        .role-card.role-card--custom {
            border-left: 4px solid #0d6efd;
        }
        .role-card__title {
            font-size: 1.05rem;
            letter-spacing: -0.02em;
        }
        .role-card__perm-groups {
            flex: 1 1 auto;
            min-height: 4rem;
        }
        .role-card__group-title {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }
        .role-card__group-list {
            font-size: 0.8125rem;
            line-height: 1.45;
            color: #334155;
            padding-left: 1.1rem;
            margin-bottom: 0.75rem;
        }
        .role-card__group-list li:last-child { margin-bottom: 0; }
        .role-card__meta {
            font-size: 0.8125rem;
            color: #64748b;
        }
        .role-card__footer {
            border-top: 1px solid rgba(15, 23, 42, 0.06);
            background: #fafbfc;
            border-radius: 0 0 12px 12px;
        }
        .roles-empty-hint {
            border: 1px dashed rgba(15, 23, 42, 0.12);
            border-radius: 12px;
            background: #f8fafc;
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
                <p class="text-muted small mb-0 mt-1">Каждая роль показана отдельной карточкой; права сгруппированы так же, как при редактировании.</p>
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

        <div class="row row-cols-1 row-cols-lg-2 g-3" id="roles-grid">
            @foreach($roles as $role)
                @php
                    $permKeys = $role->pagePermissions->pluck('page_key')->all();
                    $permCount = count($permKeys);
                    $permWord = match (true) {
                        $permCount % 10 === 1 && $permCount % 100 !== 11 => 'право',
                        in_array($permCount % 10, [2, 3, 4], true) && ! in_array($permCount % 100, [12, 13, 14], true) => 'права',
                        default => 'прав',
                    };
                @endphp
                <div class="col role-card-wrap" data-role-name="{{ mb_strtolower($role->name) }}">
                    <div class="role-card role-card--{{ $role->is_system ? 'system' : 'custom' }} bg-white">
                        <div class="p-3 pb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <div class="role-card__title fw-semibold text-truncate" title="{{ $role->name }}">{{ $role->name }}</div>
                                    <div class="role-card__meta mt-1">
                                        @if($role->is_system)
                                            <span class="badge rounded-pill text-bg-secondary">Системная</span>
                                        @else
                                            <span class="badge rounded-pill text-bg-primary">Кастомная</span>
                                        @endif
                                        <span class="ms-1">{{ $permCount }} {{ $permWord }}</span>
                                    </div>
                                </div>
                                <div class="dropdown flex-shrink-0">
                                    <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Действия">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}">
                                                <i class="bi bi-pencil-square me-2 text-primary"></i>Редактировать
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
                        <div class="px-3 role-card__perm-groups">
                            @if($permCount === 0)
                                <p class="text-muted small mb-0 fst-italic">Нет назначенных прав доступа.</p>
                            @else
                                @foreach($groupedPages as $sectionTitle => $sectionItems)
                                    @php
                                        $inSection = [];
                                        foreach ($sectionItems as $key => $label) {
                                            if (in_array($key, $permKeys, true)) {
                                                $inSection[] = $label;
                                            }
                                        }
                                    @endphp
                                    @if(count($inSection) > 0)
                                        <div class="role-card__group">
                                            <div class="role-card__group-title">{{ $sectionTitle }}</div>
                                            <ul class="role-card__group-list mb-0">
                                                @foreach($inSection as $line)
                                                    <li>{{ $line }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                        <div class="role-card__footer p-2 px-3 mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}">
                                <i class="bi bi-sliders me-1"></i>Настроить права
                            </button>
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
    <div class="modal fade" id="editRole{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование: {{ $role->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="/roles/{{ $role->id }}/edit" method="post">
                    @csrf
                    <div class="modal-body modal-perms-body">
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
                        @include('Employees.partials.role_permissions_form', [
                            'groupedPages' => $groupedPages,
                            'idPrefix' => 'r'.$role->id,
                            'editableRole' => $role,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая кастомная роль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form action="/roles/create" method="post">
                @csrf
                <div class="modal-body modal-perms-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Название роли</label>
                        <input type="text" name="name" class="form-control" required placeholder="Например, Куратор группы">
                    </div>
                    <label class="form-label fw-semibold d-block mb-2">Доступ к разделам</label>
                    @include('Employees.partials.role_permissions_form', [
                        'groupedPages' => $groupedPages,
                        'idPrefix' => 'create',
                        'editableRole' => null,
                    ])
                </div>
                <div class="modal-footer">
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
