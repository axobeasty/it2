<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Настройки расписания</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        body { background: #eef2f8; }
        .card-soft { border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
    </style>
</head>
<body>
@include('layout.nav')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Настройки конструктора расписания</h4>
            <p class="text-muted small mb-0">Время пар считается от начала первого занятия, длительности пары и перемены между парами.</p>
        </div>
        <a href="{{ route('schedule.constructor') }}" class="btn btn-outline-primary btn-sm">К конструктору</a>
    </div>

    <div class="card card-soft mb-4">
        <div class="card-body">
            <h6 class="mb-3">Параметры сетки расписания</h6>
            <form method="post" action="{{ route('schedule.constructor.settings.save') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Начало первого занятия</label>
                    <input type="time" name="first_lesson_start" class="form-control" required
                           value="{{ old('first_lesson_start', \Illuminate\Support\Str::substr($config->first_lesson_start, 0, 5)) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Продолжительность занятия</label>
                    <select name="lesson_duration_minutes" class="form-select" required>
                        @foreach($durationOptions as $minutes => $label)
                            <option value="{{ $minutes }}" @selected((int) old('lesson_duration_minutes', $config->lesson_duration_minutes) === (int) $minutes)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Перемена между парами (мин.)</label>
                    <input type="number" name="break_minutes" class="form-control" min="0" max="45" required value="{{ (int) old('break_minutes', $config->break_minutes) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Макс. число пар в день</label>
                    <input type="number" name="max_slots_per_day" class="form-control" min="1" max="20" required value="{{ (int) old('max_slots_per_day', $config->max_slots_per_day) }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body">
            <h6 class="mb-3">Предметы для расписания</h6>
            <form method="post" action="{{ route('schedule.subjects.store') }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Название предмета</label>
                    <input type="text" name="name" class="form-control" required maxlength="255" placeholder="Например, Высшая математика" value="{{ old('name') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i> Добавить предмет</button>
                </div>
            </form>

            @if($subjects->isEmpty())
                <p class="text-muted mb-0">Предметов пока нет. Добавьте хотя бы один, чтобы составлять расписание в конструкторе.</p>
            @else
                <ul class="list-group list-group-flush border rounded">
                    @foreach($subjects as $subject)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $subject->name }}</span>
                            <a href="{{ route('schedule.subjects.delete', $subject->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить предмет?');">Удалить</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
