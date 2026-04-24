<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} - Администрирование тестов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Администрирование тестов</h4>
            <a href="/tests/stats" class="btn btn-outline-dark btn-sm">Статистика</a>
        </div>
        <hr>
        <form action="/tests/admin/create" method="post" id="testBuilderForm">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Название теста</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Лимит времени (мин)</label>
                    <input type="number" name="time_limit_minutes" class="form-control" min="1" max="480">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Лимит попыток</label>
                    <input type="number" name="attempts_limit" class="form-control" min="1" max="20" placeholder="Пусто = без лимита">
                </div>
                <div class="col-md-12 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked value="1">
                        <label class="form-check-label" for="isActive">Тест активен</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label">Выдать группам</label>
                <select name="group_ids[]" class="form-select" multiple required style="min-height:120px;">
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Конструктор теста</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addQuestionBtn">
                    <i class="bi bi-plus-lg"></i> Добавить вопрос
                </button>
            </div>
            <div id="questionsContainer"></div>
            <button type="submit" class="btn btn-dark mt-3">Создать тест</button>
        </form>
    </div>

    <div class="bg-white rounded shadow-sm p-4">
        <h5 class="mb-3">Существующие тесты</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Тест</th>
                    <th>Вопросов</th>
                    <th>Лимиты</th>
                    <th>Группы</th>
                    <th>Статус</th>
                    <th class="text-end">Действие</th>
                </tr>
                </thead>
                <tbody>
                @forelse($tests as $test)
                    <tr>
                        <td>{{ $test->title }}</td>
                        <td>{{ $test->questions->count() }}</td>
                        <td>
                            <div class="small">Время: {{ $test->time_limit_minutes ? $test->time_limit_minutes . ' мин' : 'без лимита' }}</div>
                            <div class="small">Попытки: {{ $test->attempts_limit ? $test->attempts_limit : 'без лимита' }}</div>
                        </td>
                        <td>
                            @foreach($test->assignments as $assignment)
                                <span class="badge text-bg-light border">{{ optional($assignment->group)->name ?? '—' }}</span>
                            @endforeach
                        </td>
                        <td>{!! $test->is_active ? '<span class="badge text-bg-success">Активен</span>' : '<span class="badge text-bg-secondary">Отключен</span>' !!}</td>
                        <td class="text-end">
                            <a href="/tests/admin/{{ $test->id }}/edit" class="btn btn-outline-primary btn-sm">Редактировать</a>
                            <form action="/tests/admin/{{ $test->id }}/toggle" method="post" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-dark btn-sm">{{ $test->is_active ? 'Отключить' : 'Включить' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Тесты пока не созданы</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="questionTemplate">
    <div class="card mb-3 question-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Вопрос <span class="q-index"></span></strong>
                <button type="button" class="btn btn-outline-danger btn-sm remove-question">Удалить</button>
            </div>
            <div class="row">
                <div class="col-md-7 mb-2">
                    <label class="form-label">Текст вопроса</label>
                    <input type="text" class="form-control q-text" required>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Тип вопроса</label>
                    <select class="form-select q-type">
                        <option value="single">Один ответ</option>
                        <option value="multiple">Несколько ответов</option>
                        <option value="match">Сопоставление</option>
                        <option value="word">Слово</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Баллы</label>
                    <input type="number" class="form-control q-points" min="1" value="1">
                </div>
            </div>
            <div class="q-body"></div>
        </div>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
<script>
    const questionsContainer = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate');
    const addQuestionBtn = document.getElementById('addQuestionBtn');

    function renderQuestionBody(card) {
        const type = card.querySelector('.q-type').value;
        const body = card.querySelector('.q-body');
        if (type === 'single' || type === 'multiple') {
            body.innerHTML = `
                <div class="mb-2">
                    <label class="form-label">Варианты (каждый с новой строки)</label>
                    <textarea class="form-control q-options" rows="4" placeholder="Вариант 1&#10;Вариант 2"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Индексы верных ответов (с 0, через запятую)</label>
                    <input type="text" class="form-control q-correct" placeholder="0 или 0,2">
                </div>
            `;
            return;
        }

        if (type === 'match') {
            body.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Левая колонка</label>
                        <textarea class="form-control q-left" rows="4" placeholder="Термин 1&#10;Термин 2"></textarea>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Правая колонка</label>
                        <textarea class="form-control q-right" rows="4" placeholder="Определение 1&#10;Определение 2"></textarea>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Правильные пары (формат: левое=правое, с новой строки)</label>
                    <textarea class="form-control q-pairs" rows="3" placeholder="Термин 1=Определение 1"></textarea>
                </div>
            `;
            return;
        }

        body.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Верные слова (через запятую)</label>
                <input type="text" class="form-control q-accepted" placeholder="mysql, postgresql">
            </div>
        `;
    }

    function refreshIndexes() {
        document.querySelectorAll('.question-card').forEach((card, idx) => {
            card.querySelector('.q-index').textContent = idx + 1;
        });
    }

    function addQuestion() {
        const fragment = template.content.cloneNode(true);
        const card = fragment.querySelector('.question-card');
        card.querySelector('.q-type').addEventListener('change', () => renderQuestionBody(card));
        card.querySelector('.remove-question').addEventListener('click', () => {
            card.remove();
            refreshIndexes();
        });
        renderQuestionBody(card);
        questionsContainer.appendChild(fragment);
        refreshIndexes();
    }

    addQuestionBtn.addEventListener('click', addQuestion);
    addQuestion();

    document.getElementById('testBuilderForm').addEventListener('submit', function (e) {
        const cards = document.querySelectorAll('.question-card');
        cards.forEach((card, idx) => {
            const type = card.querySelector('.q-type').value;
            const text = card.querySelector('.q-text').value;
            const points = card.querySelector('.q-points').value || 1;
            const appendInput = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                this.appendChild(input);
            };

            appendInput(`questions[${idx}][type]`, type);
            appendInput(`questions[${idx}][question_text]`, text);
            appendInput(`questions[${idx}][points]`, points);

            if (type === 'single' || type === 'multiple') {
                card.querySelector('.q-options').value.split('\n').map(v => v.trim()).filter(Boolean)
                    .forEach((opt, i) => appendInput(`questions[${idx}][options][${i}]`, opt));
                card.querySelector('.q-correct').value.split(',').map(v => v.trim()).filter(Boolean)
                    .forEach((correct, i) => appendInput(`questions[${idx}][correct][${i}]`, correct));
            } else if (type === 'match') {
                card.querySelector('.q-left').value.split('\n').map(v => v.trim()).filter(Boolean)
                    .forEach((left, i) => appendInput(`questions[${idx}][left][${i}]`, left));
                card.querySelector('.q-right').value.split('\n').map(v => v.trim()).filter(Boolean)
                    .forEach((right, i) => appendInput(`questions[${idx}][right][${i}]`, right));
                card.querySelector('.q-pairs').value.split('\n').map(v => v.trim()).filter(Boolean)
                    .forEach((line) => {
                        const [left, right] = line.split('=');
                        if (left && right) {
                            appendInput(`questions[${idx}][pairs][${left.trim()}]`, right.trim());
                        }
                    });
            } else {
                card.querySelector('.q-accepted').value.split(',').map(v => v.trim()).filter(Boolean)
                    .forEach((word, i) => appendInput(`questions[${idx}][accepted][${i}]`, word));
            }
        });
    });
</script>
</body>
</html>
