<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} - Статистика тестирования</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Статистика по решенным тестам</h4>
            <a href="/tests/admin" class="btn btn-outline-dark btn-sm">К тестам</a>
        </div>
        <p class="small text-muted mb-0">Оценка по проценту верных ответов: 5 — от 90%, 4 — от 75%, 3 — от 60%, иначе 2.</p>
        <form method="get" action="/tests/stats" class="row g-2 align-items-end flex-wrap">
            <div class="col-md-4">
                <label class="form-label">Фильтр по группе</label>
                <select name="group_id" class="form-select">
                    <option value="0">Все группы</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @if($groupId === (int)$group->id) selected @endif>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto d-flex flex-wrap gap-2 align-items-end">
                <button class="btn btn-dark" type="submit">Применить</button>
                <button class="btn btn-outline-success" type="submit" formaction="/tests/stats/export" formmethod="get" title="Файл UTF-8 с разделителем «;» — открывается в Excel">Выгрузить в Excel</button>
                <button class="btn btn-outline-secondary" type="submit" formaction="/tests/stats/print" formmethod="get" formtarget="_blank">Версия для печати</button>
            </div>
        </form>
        <p class="small text-muted mb-0 mt-2">Текущий фильтр: {{ $filterLabel }}</p>
    </div>

    <div class="bg-white rounded shadow-sm p-4 mb-4">
        <h5 class="mb-3">Сводка по группам</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Группа</th>
                    <th>Попыток</th>
                    <th>Средний %</th>
                    <th>Мин %</th>
                    <th>Макс %</th>
                </tr>
                </thead>
                <tbody>
                @forelse($statsByGroup as $groupName => $stat)
                    <tr>
                        <td>{{ $groupName }}</td>
                        <td>{{ $stat['count'] }}</td>
                        <td>{{ $stat['avg'] }}</td>
                        <td>{{ $stat['min'] }}</td>
                        <td>{{ $stat['max'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Нет данных</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm p-4">
        <h5 class="mb-3">Детализация попыток</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Студент</th>
                    <th>Группа</th>
                    <th>Тест</th>
                    <th>Результат</th>
                    <th>Оценка</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                @forelse($attempts as $attempt)
                    <tr>
                        <td>{{ optional($attempt->student)->fio ?: '—' }}</td>
                        <td>{{ optional(optional($attempt->student)->group)->name ?: '—' }}</td>
                        <td>{{ optional($attempt->test)->title ?: '—' }}</td>
                        <td>{{ $attempt->score }}/{{ $attempt->max_score }} ({{ $attempt->percentage }}%)</td>
                        <td><span class="badge text-bg-primary">{{ $attempt->display_grade }}</span></td>
                        <td>{{ $attempt->submitted_at ?: $attempt->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Попыток пока нет</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
