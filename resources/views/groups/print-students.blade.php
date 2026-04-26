<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Список студентов — {{ $group->name }} — {{ $settings->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-sheet { box-shadow: none !important; border: none !important; }
        }
        body { background: #f0f2f5; }
        .print-sheet {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,.08);
        }
    </style>
</head>
<body class="py-4">
<div class="container">
    <div class="no-print mb-3 d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Печать
        </button>
        <a href="/groups" class="btn btn-outline-secondary">Назад к группам</a>
    </div>

    <div class="print-sheet p-4 p-md-5">
        <h1 class="h4 mb-1">Список студентов группы</h1>
        <p class="fs-5 fw-semibold mb-1">{{ $group->name }}</p>
        @if($group->description)
            <p class="text-muted small mb-3">{{ $group->description }}</p>
        @endif
        <p class="small text-muted mb-4">Сформировано: {{ now()->format('d.m.Y H:i') }}</p>

        @if($group->students->isEmpty())
            <p class="text-muted mb-0">В группе нет студентов.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th scope="col" style="width:3rem;" class="text-center">№</th>
                        <th scope="col">ФИО</th>
                        <th scope="col">Электронная почта</th>
                        <th scope="col">Логин</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($group->students as $i => $student)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $student->fio }}</td>
                            <td>{{ $student->email ?: '—' }}</td>
                            <td>{{ $student->login }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0">Всего: {{ $group->students->count() }} чел.</p>
        @endif
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
