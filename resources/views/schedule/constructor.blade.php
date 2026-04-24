<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->title }} — Конструктор расписания</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <style>
        body { background: #eef2f8; }
        .card-soft { border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
        .table-compact td { vertical-align: middle; }
        .table-compact input, .table-compact select { font-size: .85rem; }
        .bootstrap-select .dropdown-toggle { min-height: 31px; font-size: .875rem; }
        .group-schedule-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: 1rem 1.25rem;
            text-align: left;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .group-schedule-card:hover {
            box-shadow: 0 8px 24px rgba(15,23,42,.08);
            border-color: #cbd5e1;
        }
        #groupScheduleModal .modal-body { overflow-y: auto; background: #f1f5f9; }
    </style>
</head>
<body>
@include('layout.nav')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="mb-1">Конструктор расписания</h4>
            <p class="text-muted mb-0 small">Время пар считается по <a href="{{ route('schedule.constructor.settings') }}">настройкам конструктора</a> (начало дня, длительность, перемена). Выберите номер пары и предмет из списка.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @php
                $prev = $weekMonday->copy()->subWeek()->toDateString();
                $next = $weekMonday->copy()->addWeek()->toDateString();
            @endphp
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('schedule.constructor', array_filter(['week' => $prev, 'group_id' => $filterGroupId])) }}"><i class="bi bi-chevron-left"></i></a>
            <span class="small text-muted">{{ $weekMonday->format('d.m.Y') }} — {{ $weekMonday->copy()->addDays(6)->format('d.m.Y') }}</span>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('schedule.constructor', array_filter(['week' => $next, 'group_id' => $filterGroupId])) }}"><i class="bi bi-chevron-right"></i></a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('schedule.constructor', array_filter(['group_id' => $filterGroupId])) }}">Текущая</a>
            @if($user->canAccessPage('schedule_constructor_settings'))
                <a class="btn btn-outline-dark btn-sm" href="{{ route('schedule.constructor.settings') }}"><i class="bi bi-gear-wide-connected"></i> Настройки</a>
            @endif
        </div>
    </div>

    @if($subjects->isEmpty())
        <div class="alert alert-warning">
            В справочнике нет предметов. <a href="{{ route('schedule.constructor.settings') }}">Добавьте предметы на странице настроек</a>, затем вернитесь в конструктор.
        </div>
    @endif

    <div class="card card-soft mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('schedule.constructor') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Неделя (любой день)</label>
                    <input type="date" name="week" class="form-control form-control-sm" value="{{ $weekMonday->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Фильтр по группе</label>
                    <select name="group_id" class="form-select form-select-sm">
                        <option value="">Все группы</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" @selected((string)$filterGroupId === (string)$g->id)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Показать</button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">Сетка: начало {{ \Illuminate\Support\Str::substr($scheduleConfig->first_lesson_start, 0, 5) }}, длительность {{ $scheduleConfig->lesson_duration_minutes }} мин., перемена {{ $scheduleConfig->break_minutes }} мин., пар в день: {{ $scheduleConfig->max_slots_per_day }}.</p>
        </div>
    </div>

    <div class="card card-soft mb-4">
        <div class="card-body">
            <h6 class="mb-3">Добавить занятие</h6>
            <form method="post" action="{{ route('schedule.entries.store') }}" class="@if($subjects->isEmpty()) opacity-50 @endif">
                @csrf
                <input type="hidden" name="week_start" value="{{ $weekMonday->toDateString() }}">
                <input type="hidden" name="filter_group_id" value="{{ $filterGroupId }}">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Группа</label>
                        <select name="group_id" class="form-select form-select-sm" required>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" @selected((string) old('group_id', $filterGroupId) === (string) $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Преподаватель</label>
                        <select name="teacher_id" class="selectpicker form-control form-control-sm" data-live-search="true" data-width="100%" data-size="8" required>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" @selected(old('teacher_id') !== null ? (string) old('teacher_id') === (string) $t->id : $loop->first)>{{ $t->fio }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">День</label>
                        <select name="weekday" class="form-select form-select-sm" required>
                            @foreach($weekdays as $num => $label)
                                <option value="{{ $num }}" @selected((string) old('weekday', array_key_first($weekdays)) === (string) $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Пара (время автоматически)</label>
                        <select name="lesson_slot" class="form-select form-select-sm" required @disabled($subjects->isEmpty())>
                            @foreach($slotOptions as $slotNum => $slotLabel)
                                <option value="{{ $slotNum }}" @selected((string) old('lesson_slot', array_key_first($slotOptions)) === (string) $slotNum)>{{ $slotLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0">Предмет</label>
                        <select name="schedule_subject_id" class="form-select form-select-sm" required @disabled($subjects->isEmpty())>
                            @foreach($subjects as $subj)
                                <option value="{{ $subj->id }}" @selected(old('schedule_subject_id') !== null ? (string) old('schedule_subject_id') === (string) $subj->id : $loop->first)>{{ $subj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Кабинет</label>
                        <input type="text" name="room" class="form-control form-control-sm" maxlength="64" placeholder="305" value="{{ old('room') }}" @disabled($subjects->isEmpty())>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Здание</label>
                        <select name="building" class="form-select form-select-sm" required @disabled($subjects->isEmpty())>
                            @foreach($buildings as $key => $bl)
                                <option value="{{ $key }}" @selected(old('building', array_key_first($buildings)) === $key)>{{ $bl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-success btn-sm w-100" @disabled($subjects->isEmpty())><i class="bi bi-plus-lg"></i> Добавить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-soft mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="mb-0">Занятия на эту неделю ({{ $entries->count() }})</h6>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="post" action="{{ route('schedule.recalculate-week') }}" class="d-inline" onsubmit="return confirm('Пересчитать время всех занятий на эту неделю по текущим настройкам сетки? Учитывается фильтр по группе (если выбран).');">
                        @csrf
                        <input type="hidden" name="week_start" value="{{ $weekMonday->toDateString() }}">
                        @if($filterGroupId)
                            <input type="hidden" name="group_id" value="{{ $filterGroupId }}">
                        @endif
                        <button type="submit" class="btn btn-outline-secondary btn-sm" @disabled($entries->isEmpty()) title="Обновить start/end по номеру пары и настройкам конструктора">
                            <i class="bi bi-arrow-clockwise"></i> Пересчитать по сетке
                        </button>
                    </form>
                    <form method="post" action="{{ route('schedule.copy-week') }}" class="d-flex align-items-center gap-2" onsubmit="return confirm('Скопировать всё расписание этой недели на следующую? В следующей неделе не должно быть занятий.');">
                        @csrf
                        <input type="hidden" name="week_start" value="{{ $weekMonday->toDateString() }}">
                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-copy"></i> На следующую неделю</button>
                    </form>
                </div>
            </div>
            @php
                $groupsById = $groups->keyBy('id');
            @endphp
            @if($constructorGroupIds->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Нет занятий на выбранную неделю. Выберите группу в фильтре выше, чтобы открыть карточку и добавить занятия.</p>
            @else
                <p class="small text-muted mb-3">Нажмите на группу, чтобы открыть полноэкранное окно с занятиями на эту неделю.</p>
                <div class="row g-3">
                    @foreach($constructorGroupIds as $gid)
                        @php
                            $gModel = $groupsById->get($gid);
                            $gName = $gModel->name ?? ('Группа #'.$gid);
                            $gCount = $entriesByGroup->get($gid, collect())->count();
                            $gCount100 = $gCount % 100;
                            $gCount10 = $gCount % 10;
                            if ($gCount100 >= 11 && $gCount100 <= 14) {
                                $gCountWord = 'занятий';
                            } elseif ($gCount10 === 1) {
                                $gCountWord = 'занятие';
                            } elseif ($gCount10 >= 2 && $gCount10 <= 4) {
                                $gCountWord = 'занятия';
                            } else {
                                $gCountWord = 'занятий';
                            }
                        @endphp
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <button type="button"
                                    class="btn group-schedule-card w-100 h-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#groupScheduleModal"
                                    data-group-id="{{ $gid }}"
                                    data-group-name="{{ $gName }}">
                                <div class="fw-semibold mb-1">{{ $gName }}</div>
                                <div class="small text-muted">{{ $gCount }} {{ $gCountWord }}</div>
                                <div class="small text-primary mt-2"><i class="bi bi-arrows-fullscreen me-1"></i>Открыть</div>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="groupScheduleModal" tabindex="-1" aria-labelledby="groupScheduleModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0 rounded-0">
                <div class="modal-header border-bottom bg-white sticky-top shadow-sm">
                    <div>
                        <h5 class="modal-title mb-0" id="groupScheduleModalTitle">Расписание группы</h5>
                        <div class="small text-muted">{{ $weekMonday->format('d.m.Y') }} — {{ $weekMonday->copy()->addDays(6)->format('d.m.Y') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    @foreach($constructorGroupIds as $gid)
                        @php
                            $gModel = $groupsById->get($gid);
                            $gName = $gModel->name ?? ('Группа #'.$gid);
                            $groupEntries = $entriesByGroup->get($gid, collect());
                        @endphp
                        <div class="group-schedule-pane d-none" data-group-id="{{ $gid }}" data-group-name="{{ $gName }}">
                            <h6 class="text-secondary mb-3">{{ $gName }}</h6>
                            @forelse($groupEntries as $e)
                                <form method="post" action="{{ route('schedule.entries.update', $e->id) }}" class="border rounded-3 p-3 mb-3 bg-white @if($subjects->isEmpty()) opacity-50 @endif">
                                    @csrf
                                    @php
                                        $editSlot = $e->lesson_slot ?? \App\Support\ScheduleSlotTime::inferSlotFromStart($scheduleConfig, (string) $e->start_time);
                                    @endphp
                                    <input type="hidden" name="week_start" value="{{ $weekMonday->toDateString() }}">
                                    <input type="hidden" name="filter_group_id" value="{{ $filterGroupId }}">
                                    <input type="hidden" name="entry_slot_anchor" value="{{ $editSlot !== null && $editSlot !== '' ? (int) $editSlot : '' }}">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">День</label>
                                            <select name="weekday" class="form-select form-select-sm">
                                                @foreach($weekdays as $num => $label)
                                                    <option value="{{ $num }}" @selected((int)$e->weekday === (int)$num)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-0">Пара</label>
                                            <select name="lesson_slot" class="form-select form-select-sm" required @disabled($subjects->isEmpty())>
                                                @foreach($slotOptions as $slotNum => $slotLabel)
                                                    <option value="{{ $slotNum }}" @selected((int) $editSlot === (int) $slotNum)>{{ $slotLabel }}</option>
                                                @endforeach
                                            </select>
                                            @php
                                                $slotForGridCheck = (int) ($e->lesson_slot ?? $editSlot ?? 0);
                                                $gridMatchesStored = $slotForGridCheck < 1 || \App\Support\ScheduleSlotTime::storedTimesMatchCurrentGridForSlot($scheduleConfig, $slotForGridCheck, (string) $e->start_time, (string) $e->end_time);
                                            @endphp
                                            <div class="small mt-1">
                                                <span class="text-muted">В расписании: {{ \Illuminate\Support\Str::substr($e->start_time, 0, 5) }}—{{ \Illuminate\Support\Str::substr($e->end_time, 0, 5) }}.</span>
                                                @unless($gridMatchesStored)
                                                    <span class="text-warning d-block mt-1">Подпись пары в списке — по <strong>текущим</strong> настройкам сетки; фактическое время занятия другое и не меняется при сохранении, пока не смените номер пары.</span>
                                                @endunless
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-0">Предмет</label>
                                            <select name="schedule_subject_id" class="form-select form-select-sm" required @disabled($subjects->isEmpty())>
                                                @foreach($subjects as $subj)
                                                    <option value="{{ $subj->id }}" @selected((int)$e->schedule_subject_id === (int)$subj->id || ($e->schedule_subject_id === null && $e->subject_title === $subj->name))>{{ $subj->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Группа</label>
                                            <select name="group_id" class="form-select form-select-sm">
                                                @foreach($groups as $g)
                                                    <option value="{{ $g->id }}" @selected($e->group_id == $g->id)>{{ $g->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Преподаватель</label>
                                            <select name="teacher_id" class="selectpicker selectpicker-defer form-control form-control-sm teacher-pick-{{ $e->id }}" data-live-search="true" data-width="100%" data-size="8">
                                                @foreach($teachers as $t)
                                                    <option value="{{ $t->id }}" @selected($e->teacher_id == $t->id)>{{ $t->fio }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small mb-0">Каб.</label>
                                            <input type="text" name="room" class="form-control form-control-sm" value="{{ $e->room }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Здание</label>
                                            <select name="building" class="form-select form-select-sm">
                                                @foreach($buildings as $key => $bl)
                                                    <option value="{{ $key }}" @selected($e->building === $key)>{{ $bl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 text-nowrap">
                                            <button type="submit" class="btn btn-sm btn-primary" @disabled($subjects->isEmpty())>Сохранить</button>
                                            <a href="{{ route('schedule.entries.delete', ['id' => $e->id, 'group_id' => $filterGroupId]) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить занятие?');">Удалить</a>
                                        </div>
                                    </div>
                                </form>
                            @empty
                                <p class="text-muted mb-0">У этой группы пока нет занятий на выбранную неделю. Добавьте занятие формой выше.</p>
                            @endforelse
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer border-top bg-white d-md-none">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/i18n/defaults-ru_RU.js"></script>
<script>
    (function () {
        if (window.jQuery && jQuery.fn.selectpicker) {
            jQuery('.selectpicker:not(.selectpicker-defer)').selectpicker();
        }

        var groupModal = document.getElementById('groupScheduleModal');
        if (!groupModal || typeof bootstrap === 'undefined') return;

        groupModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var gid = btn && btn.getAttribute('data-group-id');
            var name = (btn && btn.getAttribute('data-group-name')) || '';
            var titleEl = document.getElementById('groupScheduleModalTitle');
            if (titleEl) {
                titleEl.textContent = name ? (name + ' — занятия на неделю') : 'Занятия на неделю';
            }
            groupModal.querySelectorAll('.group-schedule-pane').forEach(function (pane) {
                var match = pane.getAttribute('data-group-id') === String(gid);
                pane.classList.toggle('d-none', !match);
            });
        });

        groupModal.addEventListener('shown.bs.modal', function () {
            if (!window.jQuery || !jQuery.fn.selectpicker) return;
            var visible = groupModal.querySelector('.group-schedule-pane:not(.d-none)');
            if (!visible) return;
            jQuery(visible).find('.selectpicker-defer').each(function () {
                var $el = jQuery(this);
                if ($el.parent().hasClass('bootstrap-select')) {
                    $el.selectpicker('refresh');
                } else {
                    $el.selectpicker();
                }
            });
        });
    })();
</script>
</body>
</html>
