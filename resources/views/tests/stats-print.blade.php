<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Статистика тестирования (печать)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #000;
            font-size: 12px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 6px 0;
        }
        h2 {
            font-size: 14px;
            margin: 18px 0 8px 0;
        }
        .meta {
            margin-bottom: 12px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background: #f2f2f2;
            text-align: left;
        }
        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom: 12px;">
    <button type="button" onclick="window.print()">Печать</button>
    <a href="/tests/stats">← К статистике</a>
</div>
<h1>Статистика по решённым тестам</h1>
<div class="meta">
    Фильтр: {{ $filterLabel }}<br>
    Сформировано: {{ $printedAt }}
</div>
<p class="meta" style="margin-top:0;">Оценка по проценту верных ответов: 5 — от 90%, 4 — от 75%, 3 — от 60%, иначе 2.</p>

<h2>Сводка по группам</h2>
<table>
    <thead>
    <tr>
        <th style="width:32%">Группа</th>
        <th style="width:14%">Попыток</th>
        <th style="width:18%">Средний %</th>
        <th style="width:18%">Мин %</th>
        <th style="width:18%">Макс %</th>
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
        <tr><td colspan="5">Нет данных</td></tr>
    @endforelse
    </tbody>
</table>

<h2>Детализация попыток</h2>
<table>
    <thead>
    <tr>
        <th style="width:20%">Студент</th>
        <th style="width:14%">Группа</th>
        <th style="width:22%">Тест</th>
        <th style="width:16%">Результат</th>
        <th style="width:8%">Оценка</th>
        <th style="width:20%">Дата</th>
    </tr>
    </thead>
    <tbody>
    @forelse($attempts as $attempt)
        @php
            $at = $attempt->submitted_at ?? $attempt->created_at;
            $atFormatted = $at ? \Illuminate\Support\Carbon::parse($at)->format('d.m.Y H:i') : '—';
        @endphp
        <tr>
            <td>{{ optional($attempt->student)->fio ?: '—' }}</td>
            <td>{{ optional(optional($attempt->student)->group)->name ?: '—' }}</td>
            <td>{{ optional($attempt->test)->title ?: '—' }}</td>
            <td>{{ $attempt->score }}/{{ $attempt->max_score }} ({{ $attempt->percentage }}%)</td>
            <td>{{ $attempt->display_grade }}</td>
            <td>{{ $atFormatted }}</td>
        </tr>
    @empty
        <tr><td colspan="6">Попыток пока нет</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
