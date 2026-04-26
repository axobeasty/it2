<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>{{ $settings->title }} — Категории заявок</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
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

        .card-custom:hover {
            transform: translateY(-2px);
        }

        .table th {
            font-weight: 500;
            color: #495057;
        }

        .badge-status {
            font-size: 0.85rem;
            padding: 0.5em 0.8em;
        }

        .dropdown-toggle::after {
            display: none;
        }

        .btn-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
                <div class="row g-3 ">

                    <!-- Основной контент -->
                    <div class="col">
                        <div class="bg-white p-4 rounded shadow-sm ">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><a href="/" class=" display text-decoration-none ">←</a> Категории заявок</h4>
                                <button type="button" class="btn btn-sm btn-gradient" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                    <i class="bi bi-plus-lg"></i> Добавить
                                </button>
                            </div>

                            <div class="table-responsive ">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Название</th>
                                        <th>Дата создания</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($categories as $category)
                                        <tr class="table-{{$category->cat_color}}">
                                            <td>
                                                <strong class="">{{ $category->name }}</strong>
                                            </td>
                                            <td>
                                                {{ $category->created_at ?? 'отсутствует' }}
                                            </td>
                                            <td class="text-end btn-actions">
                                                <div class="btn-group" role="group">
                                                   @if($category->id  != 1)
                                                        <a href="/orders/categories/delete/{{ $category->id }}" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                   @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-5">
                                                <p class="mt-3 mb-0">Категории не найдены. Добавьте первую.</p>
                                            </td>
                                        </tr>
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

<!-- Модальное окно создания категории -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title">Новая категория</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/orders/categories/create" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="name" class="form-control" placeholder="Например: Электрика" required>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Цвет категории</label>
                        <select class="form-select" name="cat_color" id="categorySelect" data-live-search="true">
                            <option selected >Выберите цвет...</option>
                            <option class="text-dark fw-bold" value="white">Белый</option>
                            <option class="text-primary fw-bold" value="primary">Cиний</option>
                            <option class="text-secondary fw-bold" value="secondary">Серый</option>
                            <option class="text-success fw-bold" value="success">Зеленый</option>
                            <option class="text-danger fw-bold" value="danger">Красный</option>
                            <option class="text-warning fw-bold" value="warning">Желтый</option>
                            <option class="text-info fw-bold" value="info">Голубой</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-gradient">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
