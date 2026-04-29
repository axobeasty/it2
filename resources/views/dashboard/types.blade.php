<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
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

        .app-shell {
            min-height: 100dvh;
        }

        @media (min-width: 992px) {
            .app-shell {
                height: 100vh;
            }
            .app-main-scroll {
                height: calc(100vh - 60px);
            }
        }

        @media (max-width: 991.98px) {
            .app-main-scroll {
                height: auto !important;
                min-height: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0 app-shell">
    <div class="row g-0">
        <div class="col-12 p-0">
            @include('layout.nav')
        </div>

        <div class="col-12 col-lg p-3 order-1 order-lg-2">
            <div class="container-fluid px-0 px-sm-3 app-main-scroll">
                <div class="row g-3">
                    <div class="col">
                        <div class="bg-white p-4 rounded shadow-sm card-custom">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><a href="/" class="display text-decoration-none">←</a> Типы портфолио</h4>
                                <a href="#" class="btn btn-sm btn-gradient" data-bs-toggle="modal" data-bs-target="#createTypeModal">
                                    <i class="bi bi-plus-lg"></i> Добавить
                                </a>

                                <div class="modal fade" id="createTypeModal" tabindex="-1" aria-labelledby="createTypeModal" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="createTypeModal">Добавить тип портфолио</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <form action="/portfolio/types/add" method="post">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label for="exampleInputEmail1" class="form-label">Название</label>
                                                        <input type="text" name="name" class="form-control">
                                                    </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отменить</button>
                                                <button type="submit" class="btn btn-primary">Добавить</button>
                                            </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="ordersTable">
                                    <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;
        max-width: 40px;
        white-space: nowrap;
        text-align: center;">ID</th>
                                        <th>Название</th>
                                        <th style="width: 120px;
        max-width: 120px;
        white-space: nowrap;
        text-align: center;">Действие</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($types as $type)
                                        <tr class="table">
                                            <td><strong>{{ $type->id }}</strong></td>
                                            <td><strong>{{ $type->name }}</strong></td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#DeleteModal{{$type->id}}"><i class="bi bi-trash3-fill"></i> Удалить</button>

                                                    <div class="modal fade" id="DeleteModal{{$type->id}}" tabindex="-1" aria-labelledby="DeleteModal{{$type->id}}Label" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="DeleteModal{{$type->id}}Label">Подтверждение</h1>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Вы действительно хотите удалить тип портфолио с названием <strong>{{$type->name}} ?</strong></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отменить</button>
                                                                    <form action="/portfolio/types/{{$type->id}}/delete" method="post" class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger">Удалить</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
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
        <div class="col-12 col-lg-2 p-0 p-lg-3 pt-lg-2 sidebar-offcanvas-column order-2 order-lg-1">
            @include('layout.sidebar_offcanvas')
        </div>
    </div>



    <!-- Модальное окно создания заявки -->


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
</body>
</html>
