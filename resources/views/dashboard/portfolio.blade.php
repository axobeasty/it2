<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        body {
            background: #f5f7fb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        main {
            height: calc(100vh - 40px);
            overflow-y: auto;
            padding: 1.5rem;
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

        .task-badge {
            font-size: 0.85rem;
            padding: 0.5em 0.8em;
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
            color: white;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }

        .btn-danger-soft {
            background: linear-gradient(135deg, #dc354510, #dc354520);
            color: #dc3545;
            border: none;
            padding: 6px 16px;
            font-size: 0.875rem;
        }

        .time-display {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }

        .header-title {
            font-weight: 600;
            color: #000;
            font-size: 1.5rem;
        }

        .notification-panel {
            height: calc(100vh - 40px);
            overflow-y: auto;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
        }

        /* Обновлённые вкладки — чистый и современный стиль */
        .profile-tabs {
            display: flex;
            gap: 12px; /* Расстояние между табами */
            background: white;
            border-radius: 12px;
        }

        .profile-tab {
            flex: 1;
            text-align: center;
            padding: 10px 14px; /* Умеренный отступ */
            border-radius: 10px; /* Скруглённые углы */
            font-weight: 500;
            color: #000; /* Чёрный текст */
            background: white; /* Белый фон неактивных */
            transition: all 0.3s ease;
            font-size: 0.92rem;
            min-width: 90px;
            border:none;
        }

        .profile-tab i {
            font-size: 1.1rem;
            margin-bottom: 4px;
            display: block;
        }

        .profile-tab.active {
            background: #d9e1ef; /* Лёгкий сероватый фон активного таба */
            color: #000; /* Текст остаётся чёрным */
            font-weight: 600;
        }

        .profile-tab:hover:not(.active) {
            background: #f8f9fa;
        }

        .tab-content-area {
            padding: 1.5rem 0;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .section-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .info-row {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed #eee;
        }

        .info-label {
            font-weight: 500;
            color: #495057;
            min-width: 120px;
        }

        .info-value {
            color: #333;
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
            <div class="notification-panel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="header-title">Профиль сотрудника: <a href="/profile" class="text-decoration-none"><span class="text-primary">{{ $user->fio }}</span></a></h5>
                    <p id="live-time" class="time-display mb-0"></p>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Достижения и активность</h4>
                    <div class="btn-group" role="group">
                        <a href="#" class="btn btn-sm btn-gradient" data-bs-toggle="modal" data-bs-target="#addportfolioModal">
                            <i class="bi bi-plus-lg"></i> Добавить
                        </a>
                        <div class="modal fade" id="addportfolioModal" tabindex="-1" aria-labelledby="addportfolioModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <form action="/portfolio/add" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header ">
                                            <h5 class="modal-title" id="addportfolioModalLabel">
                                                <i class="bi bi-file-earmark-plus"></i> Добавить достижение
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Тип конференции -->
                                            <div class="mb-3">
                                                <label for="type_id" class="form-label fw-bold">Тип конференции</label>
                                                <select name="type_id" id="type_id" class="form-select form-select-lg" required>
                                                    <option value="" disabled selected>Выберите тип</option>
                                                    @foreach($portfolioTypes as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!-- Название -->
                                            <div class="mb-3">
                                                <label for="title" class="form-label fw-bold">Название</label>
                                                <input type="text"
                                                       class="form-control form-control-lg"
                                                       id="title"
                                                       name="title"
                                                       placeholder="Введите название работы"
                                                       required>
                                            </div>
                                            <!-- Загрузка файлов -->
                                            <div class="mb-3">
                                                <label for="file" class="form-label fw-bold">Файл</label>
                                                <input type="file"
                                                       class="form-control form-control-lg"
                                                       id="file"
                                                       name="file"
                                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                                                <div class="text-muted small mt-1">
                                                    Поддерживаемые форматы: PDF, DOC, PPT, JPG, PNG. Можно загрузить один файл.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="bi bi-x-circle"></i> Отмена
                                            </button>
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="bi bi-save"></i> Сохранить
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    @include('dashboard.partials.portfolio_grid')
                </div>

                </div>
            </div>
        </div>
    </div>
</div>
{!! Toastr::message() !!}
<script>
    (function tickLiveTime() {
        var el = document.getElementById('live-time');
        if (!el) return;
        el.textContent = new Date().toLocaleString('ru-RU');
        setTimeout(tickLiveTime, 1000);
    })();
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
@include('dashboard.partials.portfolio_grid_script')
</body>
</html>
