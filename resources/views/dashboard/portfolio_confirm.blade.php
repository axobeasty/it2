<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Подтверждение портфолио</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        body { background: #eaeff6; font-family: 'Segoe UI', sans-serif; min-height: 100vh; margin: 0; }
        .card-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }
        .header-title { font-weight: 600; color: #000; font-size: 1.5rem; }
    </style>
</head>
<body>
<div class="container-fluid p-0" style="height: 100vh;">
    <div class="row g-0">
        @include('layout.nav')
        <div class="col-12 col-lg-2 p-3 pt-2 pt-lg-3 sidebar-offcanvas-column">
            @include('layout.sidebar_offcanvas')
        </div>
        <div class="col-12 col-lg p-3">
            <div class="bg-white p-4 rounded shadow-sm card-custom">
                <h4 class="header-title mb-1">Подтверждение портфолио</h4>
                <p class="text-muted small mb-4">Выберите пользователя и утвердите или отклоните позиции со статусом «на проверке».</p>

                <form method="get" action="{{ route('portfolio.confirm') }}" class="row g-2 align-items-end mb-4">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Пользователь</label>
                        <select name="employee_id" class="form-select" onchange="this.form.submit()">
                            <option value="">— Выберите —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected((string) request('employee_id') === (string) $emp->id)>{{ $emp->fio }} (id {{ $emp->id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Показать</button>
                    </div>
                </form>

                @if($selected)
                    <p class="small text-muted mb-3">Портфолио: <strong>{{ $selected->fio }}</strong></p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>№</th>
                                <th>Дата</th>
                                <th>Тип</th>
                                <th>Название</th>
                                <th>Файл</th>
                                <th>Статус</th>
                                <th class="text-end">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $item->number }}</td>
                                    <td>{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                                    <td>{{ optional($item->portfolioType)->name ?? '—' }}</td>
                                    <td>{{ $item->title }}</td>
                                    <td>
                                        @if($item->file_path)
                                            <a href="{{ route('portfolio.file', $item) }}" target="_blank" rel="noopener">Открыть</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @switch((int) $item->status)
                                            @case(0)
                                                <span class="badge text-bg-secondary">На проверке</span>
                                                @break
                                            @case(1)
                                                <span class="badge text-bg-success">Утверждено</span>
                                                @break
                                            @case(2)
                                                <span class="badge text-bg-danger">Отклонено</span>
                                                @break
                                            @default
                                                <span class="badge text-bg-light text-dark">—</span>
                                        @endswitch
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if((int) $item->status === 0)
                                            <form method="post" action="{{ route('portfolio.confirm.approve', $item->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Утвердить</button>
                                            </form>
                                            <form method="post" action="{{ route('portfolio.confirm.reject', $item->id) }}" class="d-inline" onsubmit="return confirm('Отклонить эту позицию?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Отклонить</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">У этого пользователя нет записей портфолио.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif(request()->filled('employee_id'))
                    <div class="alert alert-warning mb-0">Пользователь не найден.</div>
                @endif
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
