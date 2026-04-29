<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Управление группами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container py-4">
    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Управление группами</h4>
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#createGroupModal">Добавить группу</button>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Студентов</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td>{{ $group->name }}</td>
                        <td>{{ $group->description ?: '—' }}</td>
                        <td>{{ $group->students_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="/groups/{{ $group->id }}/print-students" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer" title="Открыть список для печати">Печать списка</a>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignStudents{{ $group->id }}">Прикрепить студентов</button>
                            <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editGroup{{ $group->id }}">Редактировать</button>
                            <form action="/groups/{{ $group->id }}/delete" method="post" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <div class="small text-muted mb-2">Студенты в группе:</div>
                            @forelse($group->students as $student)
                                <span class="badge text-bg-light border me-1 mb-1">
                                    {{ $student->fio }}
                                    <form action="/groups/students/{{ $student->id }}/detach" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ms-1 text-danger text-decoration-none border-0 bg-transparent p-0">x</button>
                                    </form>
                                </span>
                            @empty
                                <span class="text-muted">Нет студентов</span>
                            @endforelse
                        </td>
                    </tr>

                    <div class="modal fade" id="editGroup{{ $group->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Редактировать группу</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="post" action="/groups/{{ $group->id }}/edit">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Название</label>
                                            <input type="text" name="name" class="form-control" value="{{ $group->name }}" required>
                                        </div>
                                        <div>
                                            <label class="form-label">Описание</label>
                                            <input type="text" name="description" class="form-control" value="{{ $group->description }}">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-dark">Сохранить</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="assignStudents{{ $group->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Прикрепить студентов к группе: {{ $group->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="post" action="/groups/{{ $group->id }}/assign-students">
                                    @csrf
                                    <div class="modal-body">
                                        <label class="form-label">Студенты (можно выбрать одного или несколько)</label>
                                        <select name="student_ids[]" class="selectpicker form-control" multiple data-live-search="true" title="Выберите студентов...">
                                            @foreach($students as $student)
                                                <option value="{{ $student->id }}">{{ $student->fio }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-dark">Прикрепить</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Группы не созданы</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $groups->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Создать группу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/groups/create">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Описание</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-dark">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
<script>
    if ($('.selectpicker').length) {
        $('.selectpicker').selectpicker();
    }
</script>
{!! Toastr::message() !!}
</body>
</html>
