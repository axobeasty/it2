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
                                <h4><a href="/" class="text-decoration-none">←</a> Администрирование заявок</h4>
                                <div class="btn-group" role="group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                                        <i class="bi bi-printer"></i> Печать
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                                        <i class="bi bi-file-earmark-excel"></i> Экспорт Excel
                                    </a>
                                    <a href="#" class="btn btn-sm btn-gradient" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                                        <i class="bi bi-plus-lg"></i> Новая
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="ordersTable">
                                    <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Описание</th>
                                        <th>Категория</th>
                                        <th>Сотрудник</th>
                                        <th>Кабинет</th>
                                        <th>Файл</th>
                                        <th style="width: 80px;
        max-width: 80px;
        white-space: nowrap;
        text-align: center;">Статус</th>
                                        <th>Дата создания</th>
                                        <th style="width: 80px;
        max-width: 80px;
        white-space: nowrap;
        text-align: center;"></th>

                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($orders as $order)
                                        <tr class="table-{{$order->category->cat_color}} ">
                                            <td><strong>{{ $order->id }}</strong></td>
                                            <td>{{ Str::limit($order->description, 50) }}</td>
                                            <td>
                                    <span class="rounded-pill badge text-bg-secondary " style="color: {{ $order->category->color }};">
                                       {{ $order->category->name }}
                                    </span>
                                            </td>
                                            <td>{{ optional(\App\Models\Employee::find($order->employee_id))->fio }}</td>
                                            <td>{{ $order->room ?? '—' }}</td>

                                            <td>
                                                @if($order->file_path)
                                                    <span class="text-muted fw-bolder">Да</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                    <span class="status-badge
                                        @if($order->status == 0) bg-warning text-dark
                                        @elseif($order->status == 1) bg-primary text-white
                                        @elseif($order->status == 2) bg-success text-white
                                        @elseif($order->status == 3) bg-danger text-white
                                        @else bg-secondary text-white @endif">
                                        @switch($order->status)
                                            @case(0) Новая @break
                                            @case(1) В процессе @break
                                            @case(2) Завершено @break\
                                            @case(3) Закрыто @break
                                        @endswitch
                                    </span>
                                            <td>{{ $order->created_at }}</td>
                                            <td class="text-center">
                                                <a href="#" class="text-decoration-none fw-bold btn btn-primary text-white btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal{{$order->id}}">
                                                    <i class="bi bi-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <div class="modal fade" id="exampleModal{{$order->id}}" tabindex="-1" aria-labelledby="exampleModal{{$order->id}}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-xl">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Заявка #{{$order->id}}</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="">
                                                            <div class="row">

                                                                <div class="col">
                                                                    <div class="mb-3">
                                                                        <label for="exampleInputEmail1" class="form-label fw-bold">Описание</label>
                                                                        <p class="p-3 rounded-3 bg-light"> {{$order->description}}</p>

                                                                    </div>

                                                                </div>
                                                                <div class="col">
                                                                    <div class="row">
                                                                        <div class="col"><div class="mb-3">
                                                                                <label for="exampleInputEmail1" class="form-label fw-bold">Категория</label>
                                                                                <p class="{{$order->category->cat_color}}">  {{ $order->category->name }}</p>

                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label for="exampleInputEmail1" class="form-label fw-bold">Сотрудник</label>
                                                                                <p class="">  {{ optional(\App\Models\Employee::find($order->employee_id))->fio }}</p>

                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label for="exampleInputEmail1" class="form-label fw-bold">Кабинет</label>
                                                                                <p class="">  {{ $order->room }}</p>

                                                                            </div></div>
                                                                        <div class="col">
                                                                            <div class="mb-3">
                                                                                <label for="exampleInputEmail1" class="form-label fw-bold">Прикрепенный документ</label>
                                                                                <p>@if($order->file_path)
                                                                                        <a href="{{ Storage::url($order->file_path) }}" target="_blank" class="file-link text-decoration-none"> {{ basename($order->file_path) }}</a>
                                                                                    @else
                                                                                        <span class="text-muted">—</span>
                                                                                    @endif</p>

                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label for="exampleInputEmail1" class="form-label fw-bold">Статус заявки</label>
                                                                                <p class="">
                                                                                @switch($order->status)
                                                                                    @case (0) <span class="status-badge text-bg-warning">Новая</span> @break
                                                                                    @case (1) <span class="status-badge text-bg-primary">В процессе</span> @break
                                                                                    @case (2) <span class="status-badge text-bg-success">Завершено</span> @break
                                                                                    @case (3) <span class="status-badge text-bg-danger">Закрыта</span> @break
                                                                                @endswitch
                                                                                </p>

                                                                            </div>

                                                                        </div>
                                                                    </div>



                                                                </div>
                                                            </div>
                                                        </form>

                                                    </div>
                                                    <div class="modal-footer">
                                                        @if($order->status == 0)
                                                            <a href="/orders/{{$order->id}}/status/set/1" class="btn btn-primary">В процессе</a>
                                                            <a href="/orders/{{$order->id}}/status/set/3" class="btn btn-danger">Закрыть принудительно</a>
                                                        @elseif($order->status == 1)
                                                            <a href="/orders/{{$order->id}}/status/set/2" class="btn btn-success">Завершено</a>
                                                            <a href="/orders/{{$order->id}}/status/set/3" class="btn btn-danger">Закрыть принудительно</a>
                                                        @elseif($order->status == 2)
                                                            <a href="/orders/{{$order->id}}/status/set/0" class="btn btn-warning">Открыть</a>
                                                        @elseif($order->status == 3)
                                                            <a href="/orders/{{$order->id}}/status/set/0" class="btn btn-warning">Открыть</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
            <form action="/orders/create" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createOrderLabel">Создание заявки</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fileInput" class="form-label">Прикрепить файл</label>
                            <input class="form-control" name="file" type="file" id="fileInput">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание заявки</label>
                            <textarea class="form-control" name="description" rows="3" id="description" placeholder="Подробности задачи..."></textarea>
                            <div class="form-text text-muted d-flex justify-content-between">
                                <span>Максимум 1000 символов!</span>
                                <span id="charCount">0 / 1000</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cabinetInput" class="form-label">Кабинет</label>
                            <input type="text" class="form-control" name="cabinetik" id="cabinetInput" placeholder="Например: 307">
                        </div>
                        <div class="mb-3">
                            <label for="categorySelect" class="form-label">Категория заявки</label>
                            <select class="selectpicker" name="category" id="categorySelect" data-live-search="true">
                                @foreach($O_categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-gradient">Создать заявку</button>
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
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    {!! Toastr::message() !!}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const textarea = document.getElementById('description');
            const charCount = document.getElementById('charCount');
            // Функция обновления счётчика
            function updateCharCount() {
                const currentLength = textarea.value.length;
                charCount.textContent = `${currentLength} / 1000`;
                // Опционально: подсветка при приближении к лимиту
                if (currentLength > 900) {
                    charCount.classList.add('text-warning');
                } else {
                    charCount.classList.remove('text-warning');
                }
                if (currentLength >= 1000) {
                    charCount.classList.replace('text-warning', 'text-danger');
                    // Ограничиваем ввод
                    textarea.value = textarea.value.substring(0, 1000);
                    charCount.textContent = `1000 / 1000`;
                }
            }
            // Слушаем события ввода и вставки
            textarea.addEventListener('input', updateCharCount);
            textarea.addEventListener('paste', function () {
                // Задержка, чтобы получить текст после вставки
                setTimeout(updateCharCount, 10);
            });
            // Инициализация при загрузке
            updateCharCount();
        });
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
        function printTable() {
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Печать заявок</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">');
            printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { padding: 8px; text-align: left; border: 1px solid #ddd; } </style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h3>Заявки — ' + new Date().toLocaleDateString('ru-RU') + '</h3>');
            printWindow.document.write(document.querySelector('#ordersTable').outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
        function exportToExcel() {
            const table = document.getElementById('ordersTable');
            const data = [];
            // Проходим по всем строкам таблицы
            for (let row of table.rows) {
                const rowData = [];
                // Проходим по ячейкам строки
                for (let cellIndex = 0; cellIndex < row.cells.length; cellIndex++) {
                    // Пропускаем нужные столбцы (например, индексы: 5 = "Действие", 3 = "Статус")
                    if ([5].includes(cellIndex)) {  // ← Укажи индексы столбцов, которые НЕ нужно экспортировать
                        continue;
                    }
                    rowData.push(row.cells[cellIndex].innerText);
                }
                data.push(rowData);
            }
            // Создаём лист вручную
            const worksheet = XLSX.utils.aoa_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Заявки");
            // Экспортируем файл
            XLSX.writeFile(workbook, `заявки_${new Date().toISOString().slice(0, 10)}.xlsx`);
        }
    </script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
</body>
</html>
