<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Раздел студента</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#eef2f8;">
@include('layout.nav')
<div class="container py-5">
    <div class="bg-white rounded shadow-sm p-4">
        <h4 class="mb-2">Раздел студента</h4>
        <p class="text-muted mb-3">Расписание занятий вашей группы доступно по ссылке ниже.</p>
        <a class="btn btn-primary" href="{{ route('schedule.my') }}">Моё расписание</a>
    </div>
</div>
</body>
</html>
