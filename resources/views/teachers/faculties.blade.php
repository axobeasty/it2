<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Факультеты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eef2f8;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Создание и управление факультетами</h4>
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newFaculty">Добавить факультет</button>
        </div>
        <table class="table table-hover align-middle">
            <thead><tr><th>Название</th><th>Кафедр</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
            @foreach($faculties as $faculty)
                <tr>
                    <td>{{ $faculty->name }}</td>
                    <td>{{ $faculty->chairs_count }}</td>
                    <td class="text-end">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newChairInFaculty{{ $faculty->id }}">Добавить кафедру</button>
                        <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editFaculty{{ $faculty->id }}">Редактировать</button>
                        <form action="/teachers/faculties/{{ $faculty->id }}/delete" method="post" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <div class="small text-muted mb-2">Кафедры факультета:</div>
                        @forelse($faculty->chairs as $chair)
                            <span class="badge text-bg-light border me-1 mb-1">{{ $chair->name }}</span>
                        @empty
                            <span class="text-muted">Кафедры не добавлены</span>
                        @endforelse
                    </td>
                </tr>
                <div class="modal fade" id="editFaculty{{ $faculty->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="/teachers/faculties/{{ $faculty->id }}/edit" method="post">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title">Редактирование факультета</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body"><input type="text" class="form-control" name="name" value="{{ $faculty->name }}" required></div>
                                <div class="modal-footer"><button class="btn btn-dark" type="submit">Сохранить</button></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="newChairInFaculty{{ $faculty->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="/teachers/chairs/create" method="post">
                                @csrf
                                <input type="hidden" name="faculty_id" value="{{ $faculty->id }}">
                                <div class="modal-header">
                                    <h5 class="modal-title">Новая кафедра в факультете "{{ $faculty->name }}"</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="text" class="form-control" name="name" placeholder="Название кафедры" required>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-dark" type="submit">Создать кафедру</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newFaculty" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/teachers/faculties/create" method="post">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Новый факультет</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><input type="text" class="form-control" name="name" placeholder="Название факультета" required></div>
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
