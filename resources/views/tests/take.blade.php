<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} - Прохождение теста</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="mb-1">{{ $test->title }}</h4>
                <p class="text-muted mb-0">{{ $test->description ?: 'Без описания' }}</p>
            </div>
            @if((int)($test->time_limit_minutes ?? 0) > 0)
                <div class="badge text-bg-warning fs-6" id="testTimer" data-seconds="{{ (int)$test->time_limit_minutes * 60 }}">
                    Осталось: --:--
                </div>
            @endif
        </div>
        <hr>
        <form method="post" action="/tests/{{ $test->id }}/submit" id="testSubmitForm">
            @csrf
            @foreach($test->questions as $qIndex => $question)
                @php
                    $options = (array) json_decode((string) $question->options_json, true);
                @endphp
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">{{ $qIndex + 1 }}. {{ $question->question_text }} <span class="text-muted small">({{ $question->points }} б.)</span></div>

                        @if($question->type === 'single')
                            @foreach(($options['options'] ?? []) as $idx => $option)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]" value="{{ $idx }}" id="q{{ $question->id }}_{{ $idx }}">
                                    <label class="form-check-label" for="q{{ $question->id }}_{{ $idx }}">{{ $option }}</label>
                                </div>
                            @endforeach
                        @elseif($question->type === 'multiple')
                            @foreach(($options['options'] ?? []) as $idx => $option)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $idx }}" id="q{{ $question->id }}_{{ $idx }}">
                                    <label class="form-check-label" for="q{{ $question->id }}_{{ $idx }}">{{ $option }}</label>
                                </div>
                            @endforeach
                        @elseif($question->type === 'match')
                            @foreach(($options['left'] ?? []) as $left)
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" value="{{ $left }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-select" name="answers[{{ $question->id }}][{{ $left }}]">
                                            <option value="">Выберите соответствие</option>
                                            @foreach(($options['right'] ?? []) as $right)
                                                <option value="{{ $right }}">{{ $right }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <input type="text" class="form-control" name="answers[{{ $question->id }}]" placeholder="Введите слово">
                        @endif
                    </div>
                </div>
            @endforeach
            <button type="submit" class="btn btn-dark">Отправить на проверку</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
<script>
    (function () {
        const timer = document.getElementById('testTimer');
        const form = document.getElementById('testSubmitForm');
        if (!timer || !form) return;

        let secondsLeft = parseInt(timer.dataset.seconds || '0', 10);
        if (!Number.isFinite(secondsLeft) || secondsLeft <= 0) return;

        const format = (seconds) => {
            const min = Math.floor(seconds / 60);
            const sec = seconds % 60;
            return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        };

        const tick = () => {
            timer.textContent = `Осталось: ${format(secondsLeft)}`;
            if (secondsLeft <= 0) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'auto_submitted';
                hidden.value = '1';
                form.appendChild(hidden);
                form.submit();
                return;
            }
            secondsLeft -= 1;
        };

        tick();
        setInterval(tick, 1000);
    })();
</script>
</body>
</html>
