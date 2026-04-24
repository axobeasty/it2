<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Печать инвентаря</title>
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
        .sign-cell {
            height: 26px;
        }
        .notes-cell {
            height: 26px;
        }
        .employee-row td {
            font-weight: bold;
            background: #fafafa;
        }
        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom: 12px;">
    <button onclick="window.print()">Печать</button>
</div>
<h1>Закрепленный инвентарь по сотрудникам</h1>
<div class="meta">Дата формирования: {{ $printedAt }}</div>

<table>
    <thead>
    <tr>
        <th style="width: 20%;">Сотрудник</th>
        <th style="width: 6%;">№</th>
        <th style="width: 24%;">Предмет</th>
        <th style="width: 14%;">Инвентарный номер</th>
        <th style="width: 10%;">Кабинет</th>
        <th style="width: 12%;">Дата закрепления</th>
        <th style="width: 10%;">Пометки</th>
        <th style="width: 8%;">Подпись</th>
    </tr>
    </thead>
    <tbody>
    @forelse($groupedByEmployee as $employeeId => $items)
        @php $employee = $items->first()->employee; @endphp
        @foreach($items as $index => $item)
            <tr>
                <td>{{ $index === 0 ? ($employee->fio ?? '—') : '' }}</td>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->store->name ?? 'Без названия' }}</td>
                <td>{{ $item->number ?? '—' }}</td>
                <td>{{ $item->room ?? '—' }}</td>
                <td>{{ $item->date_in ?? '—' }}</td>
                <td class="notes-cell"></td>
                <td class="sign-cell"></td>
            </tr>
        @endforeach
        <tr><td colspan="8" style="height: 10px; border: 0;"></td></tr>
    @empty
        <tr>
            <td colspan="8">Нет данных для печати</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
