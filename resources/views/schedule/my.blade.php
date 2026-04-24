<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Расписание</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #eef2f8; }
        .schedule-card { border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
        .lesson-pill { border-left: 4px solid #0d6efd; background: #f8fafc; border-radius: 0 10px 10px 0; padding: .75rem 1rem; margin-bottom: .6rem; }
        .week-nav .btn { border-radius: 10px; }
        .day-col h5 { font-size: .95rem; color: #475569; margin-bottom: .75rem; padding-bottom: .4rem; border-bottom: 2px solid #e2e8f0; }
    </style>
</head>
<body>
@include('layout.nav')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Расписание группы</h4>
            @if(!$noGroup && $employee->group)
                <p class="text-muted mb-0">{{ $employee->group->name }} · неделя с {{ $weekMonday->format('d.m.Y') }}</p>
            @endif
        </div>
        <div class="week-nav d-flex align-items-center gap-2">
            @php
                $prev = $weekMonday->copy()->subWeek()->toDateString();
                $next = $weekMonday->copy()->addWeek()->toDateString();
            @endphp
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('schedule.my', ['week' => $prev]) }}"><i class="bi bi-chevron-left"></i></a>
            <span class="small text-muted px-2">{{ $weekMonday->format('d.m.Y') }} — {{ $weekMonday->copy()->addDays(6)->format('d.m.Y') }}</span>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('schedule.my', ['week' => $next]) }}"><i class="bi bi-chevron-right"></i></a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('schedule.my') }}">Текущая</a>
        </div>
    </div>

    @if($noGroup)
        <div class="alert alert-warning schedule-card">Вам ещё не назначена группа. Обратитесь к администратору.</div>
    @else
        <div class="row g-3">
            @foreach(\App\Models\GroupScheduleEntry::WEEKDAY_LABELS as $dow => $dayLabel)
                <div class="col-12 col-md-6 col-xl-4 day-col">
                    <div class="card schedule-card h-100">
                        <div class="card-body">
                            <h5>{{ $dayLabel }}</h5>
                            @php $dayLessons = $entriesByWeekday->get($dow, collect()); @endphp
                            @forelse($dayLessons as $entry)
                                <div class="lesson-pill">
                                    <div class="fw-semibold">{{ optional($entry->scheduleSubject)->name ?? $entry->subject_title }}</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-clock"></i>
                                        @if($entry->lesson_slot)
                                            {{ $entry->lesson_slot }}-я пара ·
                                        @endif
                                        {{ \Illuminate\Support\Str::substr($entry->start_time, 0, 5) }} — {{ \Illuminate\Support\Str::substr($entry->end_time, 0, 5) }}
                                    </div>
                                    <div class="small mt-1">
                                        <i class="bi bi-person-video3 text-primary"></i> {{ $entry->teacher->fio ?? '—' }}
                                    </div>
                                    <div class="small mt-1">
                                        <i class="bi bi-door-open text-secondary"></i>
                                        {{ $entry->room ? 'каб. '.$entry->room : 'кабинет не указан' }}
                                        · {{ $entry->buildingLabel() }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Нет занятий</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
