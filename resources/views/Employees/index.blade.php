<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Пользователи</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

</head>
<body>
<style>
    body{
        background: #eaeff6;
    }
    .page-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    }
    .page-title {
        font-weight: 600;
        margin: 0;
    }
    .action-bar {
        gap: .5rem;
    }
    .employee-table th {
        white-space: nowrap;
        font-weight: 600;
    }
    /* Модалка создания: на весь экран, форма на всю высоту, скролл только у тела */
    #emcreate .modal-dialog.modal-fullscreen {
        height: 100%;
    }
    #emcreate .modal-content {
        height: 100%;
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }
    #emcreate .modal-header {
        flex-shrink: 0;
    }
    #emcreate .create-user-form {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }
    #emcreate .create-user-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
</style>

    @include('layout.nav')
<div class="container py-4">
    <div class="page-card p-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h4 class="page-title">Пользователи</h4>
            <div class="d-flex flex-wrap action-bar">
                <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#emcreate">
                    <i class="bi bi-plus-lg"></i> Добавить пользователя
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Функция будет добавлена позже">
                    <i class="bi bi-filetype-csv"></i> Импорт из CSV
                </button>
            </div>
        </div>
    </div>
    <div class="page-card p-4">
        <div class="table-responsive">
        <table id="myTable" class="table table-hover align-middle employee-table">
            <thead>
            <tr>
                <th>Логин</th>
                <th>Фио</th>
                <th>Подразделение</th>
                <th>Кабинет</th>
                <th>Статус</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td>{{$employee->login}}</td>
                    <td><a href="" class="text-decoration-none cursor-pointer " data-bs-toggle="modal" data-bs-target="#exampleModal{{$employee->id}}1">{{$employee->fio}}</a>
                        <div class="modal fade" id="exampleModal{{$employee->id}}1" tabindex="-1" aria-labelledby="exampleModalLabel2" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel2">Редактирование профиля (<span class="lead">{{$employee->fio}}</span>)</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="">
                                            <form action="/employees/edit/{{$employee->id}}" method="post">
                                                @csrf
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="exampleInputEmail1" class="form-label">ФИО</label>
                                                            <input type="text" class="form-control" name="fio" value="{{$employee->fio}}" aria-describedby="emailHelp">

                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="exampleInputEmail1" class="form-label">Логин</label>
                                                            <input type="text" class="form-control" name="login" value="{{$employee->login}}" aria-describedby="emailHelp">

                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="exampleInputEmail1" class="form-label">Почта</label>
                                                            <input type="email" class="form-control" name="email" value="{{$employee->email}}" aria-describedby="emailHelp">

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="exampleInputEmail1" class="form-label">Подразделение</label>
                                                            <select class="form-select " name="department_id">
                                                                <option selected value="{{$employee->department_id}}">{{ optional($employee->department)->title }}</option>
                                                                @foreach ($departments as $dep)
                                                                    @if($employee->department_id != $dep->id)
                                                                        <option value="{{$dep->id}}">{{$dep->title}}</option>
                                                                    @endif
                                                                    @endforeach


                                                            </select>

                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label class="form-label">Роль</label>
                                                            <select class="form-select role-select-edit" name="role_id">
                                                                @foreach ($roles as $role)
                                                                    <option value="{{$role->id}}" @if(trim((string) $role->name) === 'Студент') data-student-role="1" @endif @if($employee->role_id == $role->id) selected @endif>{{$role->name}}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="exampleInputEmail1" class="form-label">Пароль</label>
                                                            <input type="password" class="form-control" name="password"  aria-describedby="emailHelp">
                                                            <div id="emailHelp" class="form-text">Установить новый пароль. <span class="text-danger">Внимание! Новый пароль придет пользователю на почту!</span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="student-fields-edit border rounded p-3 mb-3" style="display:none;">
                                                    <h6 class="mb-3">Данные студента</h6>
                                                    <div class="mb-2">
                                                        <label class="form-label">Группа</label>
                                                        <select class="form-select" name="group_id">
                                                            <option value="">Выберите группу</option>
                                                            @foreach($groups as $group)
                                                                <option value="{{ $group->id }}" @if((int)$employee->group_id === (int)$group->id) selected @endif>{{ $group->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">Или создать новую группу</label>
                                                        <input type="text" class="form-control" name="new_group_name" placeholder="Например, ИВТ-21">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-2"><label class="form-label">Курс</label><input type="text" class="form-control" name="course" value="{{ $employee->course }}"></div>
                                                        <div class="col-md-6 mb-2"><label class="form-label">Номер зачетной книжки</label><input type="text" class="form-control" name="record_book_number" value="{{ $employee->record_book_number }}"></div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">Факультет</label>
                                                            <select class="form-select" name="faculty_id">
                                                                <option value="">Выберите факультет</option>
                                                                @foreach($faculties as $faculty)
                                                                    <option value="{{ $faculty->id }}" @if((int)$employee->faculty_id === (int)$faculty->id) selected @endif>{{ $faculty->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">Кафедра</label>
                                                            <select class="form-select" name="chair_id">
                                                                <option value="">Выберите кафедру</option>
                                                                @foreach($chairs as $chair)
                                                                    <option value="{{ $chair->id }}" @if((int)$employee->chair_id === (int)$chair->id) selected @endif>{{ $chair->name }} @if($chair->faculty)({{ $chair->faculty->name }})@endif</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-2"><label class="form-label">Дата рождения</label><input type="date" class="form-control" name="birth_date" value="{{ $employee->birth_date }}"></div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">Гражданство</label>
                                                            <select class="form-select" name="citizenship">
                                                                <option value="">Выберите гражданство</option>
                                                                @foreach($citizenships as $citizenship)
                                                                    <option value="{{ $citizenship['name'] }}" @if($employee->citizenship === $citizenship['name']) selected @endif>{{ $citizenship['flag'] }} {{ $citizenship['name'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-2"><label class="form-label">Номер телефона</label><input type="text" class="form-control" name="phone" value="{{ $employee->phone }}"></div>
                                                        <div class="col-md-6 mb-2"><label class="form-label">Год поступления</label><input type="text" class="form-control" name="enrollment_year" value="{{ $employee->enrollment_year }}"></div>
                                                    </div>
                                                </div>
                                                <div class="row ">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="exampleInputEmail1" class="form-label">Кабинет</label>
                                                            <input type="text" class="form-control" name="room"  value="{{$employee->room}}" aria-describedby="emailHelp">

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Доступ в систему</label>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" name="active" value="1" id="activeSwitch{{ $employee->id }}" @checked((int)$employee->active === 1)>
                                                                <label class="form-check-label" for="activeSwitch{{ $employee->id }}">Аккаунт активирован (вход разрешён)</label>
                                                            </div>
                                                            <div class="form-text">Снимите флажок, чтобы заблокировать вход без удаления учётной записи. Права задаются ролью в разделе «Роли».</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-dark">Сохранить изменения</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </td>
                    <td>{{ optional($employee->department)->title }}</td>
                    <td>{{$employee->room}}</td>
                    <td>
                        @if($employee->active == 1)
                            <form action="/employees/deactivate/{{$employee->id}}" method="post" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="border-0 bg-transparent p-0">
                                    <span class="badge text-bg-success">Активен</span>
                                </button>
                            </form>
                        @else
                            <form action="/employees/activate/{{$employee->id}}" method="post" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="border-0 bg-transparent p-0">
                                    <span class="badge text-bg-secondary">Неактивен</span>
                                </button>
                            </form>
                        @endif
                        <span class="badge text-bg-light border">{{ optional($employee->role)->name }}</span>
                    </td>
                    <td>
                        <div class="text-end">

                            <div class="dropdown">
                                <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Действие
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#exampleModal{{$employee->id}}1">Редактировать</a></li>
                                    <li><a class="dropdown-item text-danger fw-semibold" href="#" data-bs-toggle="modal" data-bs-target="#exampleModal{{$employee->login}}">Удалить</a></li>


                                </ul>
                                <div class="modal fade" id="exampleModal{{$employee->login}}" tabindex="-1" aria-labelledby="exampleModalLabel{{$employee->login}}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel{{$employee->login}}">Подтверждение</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Вы подтверждаете удаление аккаунта с логином <span class="badge text-bg-secondary">{{$employee->login}}</span>?
                                                <p class="text-danger text-center fw-bold">Внимание! Аккаунт будет удален безвозвратно!</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отменить</button>
                                                <form action="/employees/delete/{{$employee->id}}" method="post" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Удалить</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </td>
                </tr>
            @endforeach

            </tbody>

        </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
<div class="container pb-4 d-flex justify-content-end">
    {{ $employees->links() }}
</div>

{{-- Вне container: корректный z-index и размер fullscreen у Bootstrap --}}
<div class="modal fade" id="emcreate" tabindex="-1" aria-labelledby="emcreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen m-0">
        <div class="modal-content rounded-0">
            <div class="modal-header py-3 flex-shrink-0 shadow-sm">
                <h2 class="modal-title fs-5 mb-0" id="emcreateLabel">Создание учётной записи пользователя</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form class="create-user-form" action="/employees/new" method="post">
                @csrf
                <div class="modal-body create-user-body py-3 px-3 px-md-4">
                    <div class="container-fluid" style="max-width: 1200px;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="createUserFio" class="form-label">ФИО пользователя</label>
                                <input type="text" name="fio" class="form-control" id="createUserFio" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label for="createUserLogin" class="form-label">Логин</label>
                                <input type="text" name="login" class="form-control" id="createUserLogin" autocomplete="username">
                            </div>
                            <div class="col-md-6">
                                <label for="createUserEmail" class="form-label">Почта</label>
                                <input type="email" name="email" class="form-control" id="createUserEmail" autocomplete="email">
                            </div>
                            <div class="col-md-6">
                                <label for="createUserDepartment" class="form-label">Подразделение</label>
                                <select class="form-select" name="department" id="createUserDepartment">
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="createUserRoom" class="form-label">Прикреплённый кабинет</label>
                                <input type="text" name="room" class="form-control" id="createUserRoom">
                            </div>
                            <div class="col-md-6">
                                <label for="createUserRole" class="form-label">Роль</label>
                                <select class="form-select role-select-create" name="role_id" id="createUserRole">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @if(trim((string) $role->name) === 'Студент') data-student-role="1" @endif>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Пароль</label>
                                <p class="form-text mb-0">Пароль генерируется системой автоматически.</p>
                            </div>
                        </div>

                        <div class="student-fields-create border rounded p-3 mt-3 bg-light" style="display: none;">
                            <h3 class="h6 mb-3 text-secondary fw-semibold">Данные студента</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Группа</label>
                                    <select class="form-select" name="group_id">
                                        <option value="">Выберите группу</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Или новая группа</label>
                                    <input type="text" class="form-control" name="new_group_name" placeholder="Например, ИВТ-21">
                                </div>
                                <div class="col-md-4"><label class="form-label">Курс</label><input type="text" class="form-control" name="course"></div>
                                <div class="col-md-4"><label class="form-label">Номер зачётной книжки</label><input type="text" class="form-control" name="record_book_number"></div>
                                <div class="col-md-4"><label class="form-label">Год поступления</label><input type="text" class="form-control" name="enrollment_year"></div>
                                <div class="col-md-6">
                                    <label class="form-label">Факультет</label>
                                    <select class="form-select" name="faculty_id">
                                        <option value="">Выберите факультет</option>
                                        @foreach($faculties as $faculty)
                                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Кафедра</label>
                                    <select class="form-select" name="chair_id">
                                        <option value="">Выберите кафедру</option>
                                        @foreach($chairs as $chair)
                                            <option value="{{ $chair->id }}">{{ $chair->name }} @if($chair->faculty)({{ $chair->faculty->name }})@endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4"><label class="form-label">Дата рождения</label><input type="date" class="form-control" name="birth_date"></div>
                                <div class="col-md-4">
                                    <label class="form-label">Гражданство</label>
                                    <select class="form-select" name="citizenship">
                                        <option value="">Выберите гражданство</option>
                                        @foreach($citizenships as $citizenship)
                                            <option value="{{ $citizenship['name'] }}">{{ $citizenship['flag'] }} {{ $citizenship['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4"><label class="form-label">Телефон</label><input type="text" class="form-control" name="phone"></div>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <p class="form-text mb-0 small"><strong>Статус:</strong> учётная запись создаётся неактивной; пользователь активирует её по ссылке из письма.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 py-3 border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-dark">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const studentRoleId = {{ (int) ($studentRoleId ?? 0) }};

    function isStudentRoleOptionSelected(selectEl) {
        if (!selectEl || selectEl.selectedIndex < 0) return false;
        const opt = selectEl.options[selectEl.selectedIndex];
        if (opt && opt.getAttribute('data-student-role') === '1') return true;
        if (studentRoleId > 0 && parseInt(selectEl.value, 10) === studentRoleId) return true;
        return false;
    }

    function initEmployeeRoleStudentToggles() {
        const emcreate = document.getElementById('emcreate');
        const createRoleSelect = emcreate ? emcreate.querySelector('.role-select-create') : null;
        const createStudentFields = emcreate ? emcreate.querySelector('.student-fields-create') : null;
        const toggleCreate = function () {
            if (!createRoleSelect || !createStudentFields) return;
            createStudentFields.style.display = isStudentRoleOptionSelected(createRoleSelect) ? '' : 'none';
        };
        if (createRoleSelect && createStudentFields) {
            createRoleSelect.addEventListener('change', toggleCreate);
            toggleCreate();
            if (emcreate) {
                emcreate.addEventListener('shown.bs.modal', toggleCreate);
            }
        }

        document.querySelectorAll('.role-select-edit').forEach(function (select) {
            const modalBody = select.closest('.modal-body');
            const studentFields = modalBody ? modalBody.querySelector('.student-fields-edit') : null;
            const toggleEdit = function () {
                if (!studentFields) return;
                studentFields.style.display = isStudentRoleOptionSelected(select) ? '' : 'none';
            };
            select.addEventListener('change', toggleEdit);
            toggleEdit();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeRoleStudentToggles);
    } else {
        initEmployeeRoleStudentToggles();
    }
})();
</script>
</body>
</html>
