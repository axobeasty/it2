<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} - Мои тесты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Тесты, выданные группе</h4>
        </div>
        <hr>
        @if((int)($user->group_id ?? 0) === 0)
            <div class="alert alert-warning mb-0">Вы не прикреплены к группе. Обратитесь к администратору.</div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Тест</th>
                    <th>Вопросов</th>
                    <th>Лимиты</th>
                    <th>Статус</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse($tests as $test)
                    @php($attempt = $attempts->get($test->id))
                    @php($attemptsDone = (int)($attemptsCountByTest[$test->id] ?? 0))
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $test->title }}</div>
                            <div class="small text-muted">{{ $test->description ?: 'Без описания' }}</div>
                        </td>
                        <td>{{ $test->questions->count() }}</td>
                        <td>
                            <div class="small">Время: {{ $test->time_limit_minutes ? $test->time_limit_minutes . ' мин' : 'без лимита' }}</div>
                            <div class="small">Попытки:
                                @if($test->attempts_limit)
                                    {{ max(0, (int)$test->attempts_limit - (int)$attemptsDone) }} из {{ $test->attempts_limit }}
                                @else
                                    без лимита
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($attempt)
                                <span class="badge text-bg-success">Решен</span>
                                <span class="small text-muted ms-2">{{ $attempt->score }}/{{ $attempt->max_score }} ({{ $attempt->percentage }}%)</span>
                                <span class="badge text-bg-primary ms-1" title="По проценту верных ответов">Оценка: {{ $attempt->display_grade }}</span>
                            @else
                                <span class="badge text-bg-secondary">Не решен</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex flex-wrap gap-1 justify-content-end">
                                @if($attempt)
                                    <a href="/tests/{{ $test->id }}/review" class="btn btn-outline-info btn-sm">Разбор</a>
                                @endif
                                @if($test->attempts_limit && $attemptsDone >= $test->attempts_limit)
                                    <button class="btn btn-outline-secondary btn-sm" disabled>Лимит исчерпан</button>
                                @else
                                    <a href="/tests/{{ $test->id }}" class="btn btn-outline-primary btn-sm">
                                        {{ $attempt ? 'Пройти еще раз' : 'Начать' }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Для вашей группы пока нет назначенных тестов</td>
                    </tr>
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
