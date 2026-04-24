<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Кафедры</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eef2f8;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Создание и управление кафедрами</h4>
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newChair">Добавить кафедру</button>
        </div>
        <table class="table table-hover align-middle">
            <thead><tr><th>Название</th><th>Факультет</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
            @foreach($chairs as $chair)
                <tr>
                    <td>{{ $chair->name }}</td>
                    <td>{{ $chair->faculty->name ?? '—' }}</td>
                    <td class="text-end">
                        <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editChair{{ $chair->id }}">Редактировать</button>
                        <a href="/teachers/chairs/{{ $chair->id }}/delete" class="btn btn-outline-danger btn-sm">Удалить</a>
                    </td>
                </tr>
                <div class="modal fade" id="editChair{{ $chair->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="/teachers/chairs/{{ $chair->id }}/edit" method="post">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title">Редактирование кафедры</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><input type="text" class="form-control" name="name" value="{{ $chair->name }}" required></div>
                                    <select class="form-select" name="faculty_id">
                                        <option value="">Без факультета</option>
                                        @foreach($faculties as $faculty)
                                            <option value="{{ $faculty->id }}" @if((int)$chair->faculty_id === (int)$faculty->id) selected @endif>{{ $faculty->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="modal-footer"><button class="btn btn-dark" type="submit">Сохранить</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newChair" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/teachers/chairs/create" method="post">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Новая кафедра</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><input type="text" class="form-control" name="name" placeholder="Название кафедры" required></div>
                    <select class="form-select" name="faculty_id">
                        <option value="">Без факультета</option>
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer"><button class="btn btn-dark" type="submit">Создать</button></div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
