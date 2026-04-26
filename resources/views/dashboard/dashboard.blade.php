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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        body {
            background: #eaeff6;
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

        .priority-urgent { background: #f8d7da; border-left: 4px solid #dc3545; }
        .priority-medium { background: #fff3cd; border-left: 4px solid #ffc107; }
        .priority-low { background: #d1ecf1; border-left: 4px solid #0dcaf0; }

        .task-item {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .task-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .dash-schedule-row {
            border-left: 3px solid #0d6efd;
            background: #f8fafc;
            border-radius: 0 10px 10px 0;
            padding: .65rem 1rem;
            margin-bottom: .5rem;
        }

        .nav-title {
            font-size: 0.875rem;
            color: #6c757d;
            padding: 0.5rem 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        <div class="col-12 col-lg-7 p-3">
            <main>
                @if($showDashboardViewSwitcher ?? false)
                <div class="card-custom bg-white mb-3 border-0">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-sliders text-primary"></i>
                                <span class="small text-muted text-uppercase fw-semibold mb-0" style="letter-spacing: .04em;">Вид главной страницы</span>
                            </div>
                            <div class="btn-group flex-wrap" role="group" aria-label="Переключение вида главной">
                                @if($canTasks ?? false)
                                <a href="{{ url('/dashboard?view=tasks') }}"
                                   class="btn btn-sm {{ ($dashboardMainBlock ?? '') === 'tasks' ? 'btn-gradient' : 'btn-outline-secondary' }}">
                                    <i class="bi bi-check2-square me-1"></i>Задачи
                                </a>
                                @endif
                                @if($canDashboardScheduleStudent ?? false)
                                <a href="{{ url('/dashboard?view=schedule') }}"
                                   class="btn btn-sm {{ ($dashboardMainBlock ?? '') === 'schedule' ? 'btn-gradient' : 'btn-outline-secondary' }}">
                                    <i class="bi bi-calendar-week me-1"></i>Расписание (студенты)
                                </a>
                                @endif
                                @if($canTeacherSchedule ?? false)
                                <a href="{{ url('/dashboard?view=schedule_teacher') }}"
                                   class="btn btn-sm {{ ($dashboardMainBlock ?? '') === 'schedule_teacher' ? 'btn-gradient' : 'btn-outline-secondary' }}">
                                    <i class="bi bi-calendar3-event me-1"></i>Расписание (преподаватель)
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if(($dashboardMainBlock ?? 'tasks') !== 'none')
                <div class="card-custom bg-white mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            @if(($dashboardMainBlock ?? 'tasks') === 'tasks')
                                <h4 class="header-title mb-0">Задачи <a type="button" href="#" data-bs-toggle="modal" data-bs-target="#addtaskl"><i class="bi bi-plus-circle-fill"></i></a></h4>
                            @elseif(($dashboardMainBlock ?? '') === 'schedule')
                                <div>
                                    <h4 class="header-title mb-0">Расписание (студенты)</h4>
                                    @if(isset($scheduleWeekMonday) && $scheduleWeekMonday)
                                        <p class="text-muted small mb-0">{{ $scheduleWeekMonday->format('d.m.Y') }} — {{ $scheduleWeekMonday->copy()->addDays(6)->format('d.m.Y') }}</p>
                                    @endif
                                </div>
                            @elseif(($dashboardMainBlock ?? '') === 'schedule_teacher')
                                <div>
                                    <h4 class="header-title mb-0">Расписание (преподаватель)</h4>
                                    @if(isset($scheduleWeekMonday) && $scheduleWeekMonday)
                                        <p class="text-muted small mb-0">{{ $scheduleWeekMonday->format('d.m.Y') }} — {{ $scheduleWeekMonday->copy()->addDays(6)->format('d.m.Y') }}</p>
                                    @endif
                                </div>
                            @endif
                            <span class="time-display" id="live-time">{{ $time ?? '' }}</span>
                        </div>

                        @if(($dashboardMainBlock ?? 'tasks') === 'tasks')
                            @if($tasks->isEmpty())
                                <div class="text-center p-5">
                                    <p class="lead text-muted">Задач не найдено. <a href="#" class="text-primary text-decoration-none" data-bs-toggle="modal" data-bs-target="#addtaskl">Добавить задачу</a></p>
                                </div>
                            @else
                                @foreach($tasks->sortBy('priority') as $task)
                                    <div class="task-item {{ $task->priority == 1 ? 'priority-urgent' : ($task->priority == 2 ? 'priority-medium' : 'priority-low') }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1 fw-semibold">{{ $task->title }}</h5>
                                                <p class="text-muted mb-2">{{ $task->description }}</p>
                                                <span class="badge task-badge text-bg-{{ $task->priority == 1 ? 'danger' : ($task->priority == 2 ? 'warning' : 'secondary') }}">
                                                    @switch($task->priority)
                                                        @case(1) Срочно @break
                                                        @case(2) Приемлемо @break
                                                        @case(3) Не срочно @break
                                                        @default Неизвестно
                                                    @endswitch
                                                </span>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-danger-soft btn-sm"
                                                        onclick="confirmDelete('{{ $task->id }}', '{{ Str::limit($task->title, 30, '...') }}')">
                                                    Удалить
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @elseif(($dashboardMainBlock ?? '') === 'schedule')
                            <div class="d-flex justify-content-end mb-3">
                                @if($canScheduleMy ?? false)
                                    <a href="{{ route('schedule.my', ['week' => $scheduleWeekMonday?->toDateString()]) }}" class="btn btn-sm btn-outline-primary">Полное расписание</a>
                                @else
                                    <span class="small text-muted">Полная страница расписания — после выдачи права «Расписание: просмотр» в роли.</span>
                                @endif
                            </div>
                            @if(!($employee->group_id ?? null))
                                <div class="alert alert-warning mb-0">Вам ещё не назначена группа. Обратитесь к администратору.</div>
                            @elseif($schedulePreview->isEmpty())
                                <p class="text-muted text-center py-4 mb-0">На эту неделю занятий нет.</p>
                            @else
                                @foreach($schedulePreview as $entry)
                                    <div class="dash-schedule-row">
                                        <div class="small text-muted">{{ $entry->weekdayLabel() }} · {{ \Illuminate\Support\Str::substr($entry->start_time, 0, 5) }}—{{ \Illuminate\Support\Str::substr($entry->end_time, 0, 5) }}</div>
                                        <div class="fw-semibold">{{ optional($entry->scheduleSubject)->name ?? $entry->subject_title }}</div>
                                        <div class="small text-secondary">{{ $entry->teacher->fio ?? '—' }}@if($entry->room) · каб. {{ $entry->room }}@endif</div>
                                    </div>
                                @endforeach
                            @endif
                        @elseif(($dashboardMainBlock ?? '') === 'schedule_teacher')
                            <div class="d-flex justify-content-end mb-3">
                                @if($canTeacherSchedule ?? false)
                                    <a href="{{ route('schedule.teacher', ['week' => $scheduleWeekMonday?->toDateString()]) }}" class="btn btn-sm btn-outline-primary">Полное расписание</a>
                                @endif
                            </div>
                            @if($scheduleTeacherPreview->isEmpty())
                                <p class="text-muted text-center py-4 mb-0">На эту неделю у вас нет занятий в расписании.</p>
                            @else
                                @foreach($scheduleTeacherPreview as $entry)
                                    <div class="dash-schedule-row" style="border-left-color: #198754;">
                                        <div class="small text-muted">{{ $entry->weekdayLabel() }} · {{ \Illuminate\Support\Str::substr($entry->start_time, 0, 5) }}—{{ \Illuminate\Support\Str::substr($entry->end_time, 0, 5) }}</div>
                                        <div class="fw-semibold">{{ optional($entry->scheduleSubject)->name ?? $entry->subject_title }}</div>
                                        <div class="small text-secondary">{{ optional($entry->group)->name ?? 'Группа' }}@if($entry->room) · каб. {{ $entry->room }}@endif</div>
                                    </div>
                                @endforeach
                            @endif
                        @endif
                    </div>
                </div>
                @else
                <div class="d-flex justify-content-end mb-2">
                    <span class="time-display text-muted" id="live-time">{{ $time ?? '' }}</span>
                </div>
                @endif
            </main>
        </div>
        <div class="col-12 col-lg-3 p-3">
            <div class="notification-panel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="header-title">Уведомления</h5>
                    <span class="position-relative" >
                            <a href="/notifications/mark-all-read" class="text-decoration-none fw-bold">Прочитать всё</a>

            </span>

                </div>
                <div class="list-group list-group-flush">
                    @if($notifs->where('is_read', false)->count() > 0)
                        @foreach($notifs->where('is_read', false)->sortByDesc('created_at') as $notification)
                            <a href="#" class="list-group-item list-group-item-action border-0 p-3 mb-2 rounded shadow-sm">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-medium">{{ $notification->title }}</h6>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 text-secondary">{{ $notification->message }}</p>
                            </a>
                        @endforeach
                    @else
                        <p class="text-center text-muted">Уведомлений нет.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(($dashboardMainBlock ?? 'tasks') === 'tasks')
<div class="modal fade" id="addtaskl" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Создание задачи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/task/add" method="post">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Заголовок</label>
                        <input type="text" name="title" class="form-control" placeholder="Краткое описание" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Подробности задачи..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="priority" class="form-label">Приоритет</label>
                        <select class="form-select" name="priority" required>
                            <option value="1">Срочно (красный)</option>
                            <option value="2">Приемлемо (жёлтый)</option>
                            <option value="3">Не срочно (синий)</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-gradient">Добавить задачу</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
@if(($dashboardMainBlock ?? 'tasks') === 'tasks')
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content card-custom border-0 shadow-sm">
            <div class="modal-body text-center p-4">
                <i class="bi bi-trash3 text-danger" style="font-size: 2.5rem;"></i>
                <h6 class="mt-3 mb-1">Удалить задачу?</h6>
                <p class="text-muted small mb-0" id="deleteTaskTitle"></p>
                <div class="d-flex justify-content-center gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Отмена</button>
                    <form id="deleteForm" action="" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm px-3">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function confirmDelete(taskId, taskTitle) {
        document.getElementById('deleteTaskTitle').textContent = taskTitle;

        const form = document.getElementById('deleteForm');
        form.action = `/task/delete/${taskId}`;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const textarea = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        if (!textarea || !charCount) return;

        function updateCharCount() {
            const currentLength = textarea.value.length;
            charCount.textContent = `${currentLength} / 1000`;

            if (currentLength > 900) {
                charCount.classList.add('text-warning');
            } else {
                charCount.classList.remove('text-warning');
            }

            if (currentLength >= 1000) {
                charCount.classList.replace('text-warning', 'text-danger');
                textarea.value = textarea.value.substring(0, 1000);
                charCount.textContent = `1000 / 1000`;
            }
        }

        textarea.addEventListener('input', updateCharCount);
        textarea.addEventListener('paste', function () {
            setTimeout(updateCharCount, 10);
        });

        updateCharCount();
    });
</script>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
</body>
</html>
