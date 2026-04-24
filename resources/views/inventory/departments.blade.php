<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Мои заявки</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        body {
            background: #eaeff6;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
        }

        .header-title {
            font-weight: 600;
            color: #000;
            font-size: 1.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
            color: white;
            padding: 6px 16px;
            transition: all 0.3s;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #0b5ed7, #0a58ca);
            color:white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }

        .card-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.5em 0.8em;
            border-radius: 50px;
        }

        .table th {
            font-weight: 500;
            color: #495057;
            cursor: pointer;
        }

        .btn-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .file-link {
            text-decoration: none;
            color: #0d6efd;
        }

        .file-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container-fluid p-0" style="height: 100vh;">
    <div class="row g-0">
        @include('layout.nav')

        <div class="col-12 col-lg-2 p-3 pt-2 pt-lg-3 sidebar-offcanvas-column">
            @include('layout.sidebar_offcanvas')
        </div>
        <div class="col-12 col-lg p-3">
            <div class="container-fluid px-3" style="height: calc(100vh - 60px);">
                <div class="row g-3">
                    <div class="col">
                        <div class="bg-white p-4 rounded shadow-sm card-custom">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><a href="/" class="display text-decoration-none">←</a> Структурные подразделения</h4>
                                <a href="#" class="btn btn-sm btn-gradient" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                                    <i class="bi bi-plus-lg"></i> Добавить новое подразделение
                                </a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="ordersTable">
                                    <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th style="width: 240px;
        max-width: 240px;
        white-space: nowrap;
        text-align: center;">Контингент</th>
                                        <th style="width: 100px;
        max-width: 100px;
        white-space: nowrap;
        text-align: center;"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($departs as $depart)
                                        <tr class="">
                                            <td><strong>{{ $depart->id }}</strong></td>
                                            <td><strong>{{ $depart->title}}</strong></td>
                                            <td>
                                                <p>
                                                    <a class="" data-bs-toggle="collapse" href="#collapseExample{{$depart->id}}" role="button" aria-expanded="false" aria-controls="collapseExample">
                                                        Посмотреть
                                                    </a>
                                                <div class="collapse border border-dark-subtle p-2 rounded" id="collapseExample{{$depart->id}}">
                                                        <a href="" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#depedit{{$depart->id}}Modal"><i class="bi bi-plus-lg"></i> Прикрепить сотрудника</a>
                                                    <!-- Modal для редактирования подразделения -->
                                                    <div class="modal fade" id="depedit{{$depart->id}}Modal" tabindex="-1" aria-labelledby="depedit{{$depart->id}}Label" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <form action="/inv/departments/{{$depart->id}}/edit" method="post">
                                                                @csrf
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h1 class="modal-title fs-5" id="depedit{{$depart->id}}Label">Изменение подразделения</h1>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <!-- Название подразделения -->
                                                                        <div class="mb-3">
                                                                            <label for="title" class="form-label">Название подразделения</label>
                                                                            <input type="text" class="form-control" value="{{$depart->title}}" name="title" id="title" required>
                                                                        </div>

                                                                        <!-- Multiselect сотрудников -->
                                                                        <div class="mb-3">
                                                                            <label for="employees" class="form-label">Выберите сотрудников</label>
                                                                            <select name="employee_ids" id="employees" class="selectpicker form-control" multiple data-live-search="true" title="Выберите сотрудников...">
                                                                                @foreach($contingent as $contig)
                                                                                    <option @if($contig->department_id == $depart->id) selected @endif value="{{ $contig->id }}">{{ $contig->fio }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                            <div id="emailHelp" class="form-text text-danger ">Имейте в виду, что, если сотрудник уже прикреплен к подразделению, то создание нового подразделения прикрепит его к вновь созданному!</div>

                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                                        <button type="submit" class="btn btn-gradient">Добавить</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                        <table class="table">
                                                                <tbody>
                                                                <strong>@foreach($contingent as $contig)
                                                                      @if($contig->department_id == $depart->id)
                                                                            <p>
                                                                                @if ($contig->department_id == $depart->id)
                                                                                    <tr class="">
                                                                                        <th scope="row" class="rounded">
                                                                                            {{ $contig->fio }}
                                                                                        </th>

                                                                                    </tr>

                                                                                @endif
                                                                            </p>
                                                                      @endif

                                                                    @endforeach</strong>


                                                                </tbody>
                                                            </table>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($depart->id != 1)
                                                    <a href="/inv/departments/delete/{{$depart->id}}" class="text-decoration-none fw-bold btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                @endif

                                            </td>
                                        </tr>
                                    @empty
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Модальное окно создания заявки -->
    <div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="/inv/departments/create" method="post">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createOrderLabel">Новое подразделение</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Название подразделения -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Название подразделения</label>
                            <input type="text" class="form-control" name="title" id="title" required>
                        </div>

                        <!-- Multiselect сотрудников -->
                        <div class="mb-3">
                            <label for="employees" class="form-label">Выберите сотрудников</label>
                            <select name="employee_ids" id="employees" class="selectpicker form-control" multiple data-live-search="true" title="Выберите сотрудников...">
                                @foreach($contingent as $contig)
                                    <option value="{{ $contig->id }}">{{ $contig->fio }}</option>
                                @endforeach
                            </select>
                            <div id="emailHelp" class="form-text text-danger ">Имейте в виду, что, если сотрудник уже прикреплен к подразделению, то создание нового подразделения прикрепит его к вновь созданному!</div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-gradient">Добавить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
            crossorigin="anonymous"></script>
    <script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    {!! Toastr::message() !!}

    <script>
        $(document).ready(function () {
            $('.selectpicker').selectpicker({
                actionsBox: true,
                selectAllText: 'Выбрать всё',
                deselectAllText: 'Снять всё',
                noneSelectedText: 'Не выбрано'
            });
            $('#ordersTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ru.json",
                    "search": "Поиск: ",
                    "searchPlaceholder: ": "Введите текст...",
                    "info": "Отображается _START_ - _END_ из _TOTAL_ записей",
                    "lengthMenu":     "Показывать _MENU_ записей",
                    paginate: {
                        "first":      "Первый",
                        "last":       "Последний",
                        "next":       "Следующий",
                        "previous":   "Предыдущий"
                    },
                    "emptyTable":     "Заявок пока нет.",
                    "infoEmpty":      "",
                    "zeroRecords":    "Ни одной записи не найдено!",
                },
                "pageLength": 10,
                "order": [[0, "desc"]],
                "lengthMenu": [5, 10, 25, 50],
                "responsive": true,
                "info": "Показано с _START_ по _END_ из _TOTAL_ записей",
                "search": "Поиск:",
                "zeroRecords": "Записи не найдены",
                "paginate": {
                    "first": "Первая",
                    "last": "Последняя",
                    "next": "Следующая",
                    "previous": "Предыдущая"
                }
            });
        });

    </script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
</body>
</html>
