<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Раздел учителя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#eef2f8;">
@include('layout.nav')
@php
    extract(\App\Support\MenuVisibility::flags($user), EXTR_SKIP);
@endphp
<div class="container py-5">
    <div class="bg-white rounded shadow-sm p-4 mb-3">
        <h4 class="mb-2">Раздел учителя</h4>
        <p class="text-muted mb-0">Здесь будут страницы, предназначенные для роли учителя.</p>
    </div>
    <div class="row g-3">
        @if($canFaculties)
        <div class="col-md-6">
            <a href="/teachers/faculties" class="text-decoration-none">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h5 class="mb-2">Факультеты</h5>
                    <p class="text-muted mb-0">Создание и управление факультетами.</p>
                </div>
            </a>
        </div>
        @endif
        @if($canChairs)
        <div class="col-md-6">
            <a href="/teachers/chairs" class="text-decoration-none">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h5 class="mb-2">Кафедры</h5>
                    <p class="text-muted mb-0">Создание и управление кафедрами.</p>
                </div>
            </a>
        </div>
        @endif
    </div>
</div>
</body>
</html>
