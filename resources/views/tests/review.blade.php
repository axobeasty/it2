<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Разбор теста</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h4 class="mb-1">{{ $test->title }}</h4>
                <p class="text-muted small mb-0">
                    Попытка от {{ $attempt->submitted_at ?: $attempt->created_at }}
                    · {{ $attempt->score }}/{{ $attempt->max_score }} баллов ({{ $attempt->percentage }}%)
                    · оценка <span class="badge text-bg-primary">{{ $attempt->display_grade }}</span>
                </p>
            </div>
            <a href="/tests" class="btn btn-outline-secondary btn-sm">К списку тестов</a>
        </div>

        @if($allAttempts->count() > 1)
            <form method="get" action="/tests/{{ $test->id }}/review" class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label small mb-0">Другая попытка</label>
                    <select name="attempt" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($allAttempts as $a)
                            <option value="{{ $a->id }}" @if((int)$a->id === (int)$attempt->id) selected @endif>
                                {{ $a->submitted_at ?: $a->created_at }} — {{ $a->score }}/{{ $a->max_score }} ({{ $a->percentage }}%), оц. {{ $a->display_grade }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                <tr>
                    <th style="width:48px">№</th>
                    <th>Вопрос</th>
                    <th>Ваш ответ</th>
                    <th style="width:120px">Баллы</th>
                    <th style="width:110px">Итог</th>
                </tr>
                </thead>
                <tbody>
                @foreach($breakdown as $row)
                    <tr class="{{ $row['is_correct'] ? 'table-success' : 'table-danger' }}">
                        <td class="text-center">{{ $row['num'] }}</td>
                        <td>
                            <div class="fw-semibold">{{ $row['question']->question_text }}</div>
                            <div class="small text-muted">{{ $row['points'] }} б. · {{ $row['question']->type === 'single' ? 'Один ответ' : ($row['question']->type === 'multiple' ? 'Несколько ответов' : ($row['question']->type === 'match' ? 'Сопоставление' : 'Слово')) }}</div>
                        </td>
                        <td><pre class="small mb-0" style="white-space:pre-wrap;font-family:inherit;">{{ $row['your_answer'] }}</pre></td>
                        <td class="text-center">{{ $row['earned'] }} / {{ $row['points'] }}</td>
                        <td class="text-center">
                            @if($row['is_correct'])
                                <span class="badge text-bg-success">Верно</span>
                            @else
                                <span class="badge text-bg-danger">Неверно</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted mb-0">Если тест редактировали после прохождения, для новых вопросов может отображаться «нет ответа» — смотрите попытку до изменения теста.</p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
