<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>{{ $settings->title }} — Менеджер паролей</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background: #eaeff6;">
<div class="container-fluid p-0 app-shell" style="min-height: 100dvh;">
    <div class="row g-0">
        <div class="col-12 p-0">
            @include('layout.nav')
        </div>
        <div class="col-12 col-lg p-3 order-1 order-lg-2">
            <div class="container-fluid px-0 px-sm-3">
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Менеджер паролей</h4>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPasswordModal">
                            <i class="bi bi-plus-lg"></i> Новая запись
                        </button>
                    </div>
                    <p class="text-muted small mb-4">
                        Пароли хранятся в зашифрованном виде. Доступ к записям имеет только владелец.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th>Логин</th>
                                <th>URL</th>
                                <th>Пароль</th>
                                <th>Заметки</th>
                                <th class="text-end">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($passwords as $entry)
                                <tr>
                                    <td>{{ $entry->title }}</td>
                                    <td>{{ $entry->login ?: '—' }}</td>
                                    <td>
                                        @if($entry->url)
                                            <a href="{{ $entry->url }}" target="_blank" rel="noopener noreferrer">{{ $entry->url }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <code id="pw-{{ $entry->id }}">••••••••</code>
                                            <button
                                                class="btn btn-outline-secondary btn-sm reveal-btn"
                                                data-id="{{ $entry->id }}"
                                                data-target="pw-{{ $entry->id }}"
                                                type="button"
                                            >
                                                Показать
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($entry->notes, 60) ?: '—' }}</td>
                                    <td class="text-end">
                                        <form action="/passwords/{{ $entry->id }}/delete" method="post" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                Удалить
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Нет сохраненных паролей
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-2 p-0 p-lg-3 pt-lg-2 sidebar-offcanvas-column order-2 order-lg-1">
            @include('layout.sidebar_offcanvas')
        </div>
    </div>
</div>

<div class="modal fade" id="addPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/passwords/create" method="post" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="title" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Логин</label>
                        <input type="text" name="login" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="url" name="url" class="form-control" maxlength="255" placeholder="https://example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Заметки</label>
                        <textarea name="notes" class="form-control" rows="3" maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
<script>
    document.querySelectorAll('.reveal-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            const entryId = this.dataset.id;
            const target = document.getElementById(this.dataset.target);
            if (!target) return;

            if (target.textContent !== '••••••••') {
                target.textContent = '••••••••';
                this.textContent = 'Показать';
                return;
            }

            try {
                const response = await fetch('/passwords/' + entryId + '/reveal', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) {
                    throw new Error('Не удалось расшифровать');
                }
                const data = await response.json();
                target.textContent = data.password ?? 'Ошибка';
                this.textContent = 'Скрыть';
            } catch (error) {
                toastr.error('Не удалось показать пароль');
            }
        });
    });
</script>
</body>
</html>
