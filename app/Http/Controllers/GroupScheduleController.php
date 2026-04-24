<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\GroupScheduleEntry;
use App\Models\Groups;
use App\Models\Roles;
use App\Models\ScheduleConstructorSetting;
use App\Models\ScheduleSubject;
use App\Models\Settings;
use App\Support\ScheduleSlotTime;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GroupScheduleController extends Controller
{
    public function mySchedule(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id', 1)->first();

        if (! $user->canAccessPage('schedule_my')) {
            Toastr::error('У вас нет доступа к просмотру расписания.', 'Ошибка доступа', ['progressBar' => true]);
            return redirect('/');
        }

        $employee = Employee::with('group', 'role')->findOrFail($user->id);

        if (! $employee->group_id) {
            return view('schedule.my', [
                'user' => $user,
                'settings' => $settings,
                'employee' => $employee,
                'weekMonday' => $this->resolveMonday($request->input('week')),
                'entriesByWeekday' => collect(),
                'noGroup' => true,
            ]);
        }

        $weekMonday = $this->resolveMonday($request->input('week'));
        $entries = GroupScheduleEntry::query()
            ->where('group_id', $employee->group_id)
            ->whereDate('week_start_date', $weekMonday->toDateString())
            ->with(['teacher', 'scheduleSubject'])
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $entriesByWeekday = $entries->groupBy('weekday');

        return view('schedule.my', [
            'user' => $user,
            'settings' => $settings,
            'employee' => $employee,
            'weekMonday' => $weekMonday,
            'entriesByWeekday' => $entriesByWeekday,
            'noGroup' => false,
        ]);
    }

    public function constructor(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id', 1)->first();

        if (! $user->canAccessPage('schedule_constructor')) {
            Toastr::error('Нет доступа к конструктору расписания.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $weekMonday = $this->resolveMonday($request->input('week'));
        $groupId = $request->input('group_id');

        $entriesQuery = GroupScheduleEntry::query()
            ->whereDate('week_start_date', $weekMonday->toDateString())
            ->with(['group', 'teacher', 'scheduleSubject'])
            ->orderBy('group_id')
            ->orderBy('weekday')
            ->orderBy('start_time');

        if ($groupId) {
            $entriesQuery->where('group_id', $groupId);
        }

        $entries = $entriesQuery->get();

        $entriesByGroup = $entries->groupBy('group_id');
        $filterGroupIdInt = $groupId !== null && $groupId !== '' ? (int) $groupId : null;
        if ($filterGroupIdInt) {
            $constructorGroupIds = collect([$filterGroupIdInt]);
        } else {
            $constructorGroupIds = $entriesByGroup->keys()->map(fn ($k) => (int) $k)->sort()->values();
        }

        $teacherRoleId = Roles::where('name', 'Преподаватель')->value('id');
        $teachersQuery = Employee::query()->orderBy('fio');
        if ($teacherRoleId) {
            $teachersQuery->where('role_id', $teacherRoleId);
        }

        $scheduleConfig = ScheduleConstructorSetting::current();
        $slotOptions = ScheduleSlotTime::slotOptions($scheduleConfig);
        $subjects = ScheduleSubject::orderBy('name')->get();

        return view('schedule.constructor', [
            'user' => $user,
            'settings' => $settings,
            'weekMonday' => $weekMonday,
            'groups' => Groups::orderBy('name')->get(),
            'teachers' => $teachersQuery->get(),
            'entries' => $entries,
            'entriesByGroup' => $entriesByGroup,
            'constructorGroupIds' => $constructorGroupIds,
            'filterGroupId' => $groupId,
            'buildings' => GroupScheduleEntry::BUILDING_LABELS,
            'weekdays' => GroupScheduleEntry::WEEKDAY_LABELS,
            'scheduleConfig' => $scheduleConfig,
            'slotOptions' => $slotOptions,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $config = ScheduleConstructorSetting::current();
        $slotKeys = array_keys(ScheduleSlotTime::slotOptions($config));

        $validator = Validator::make($request->all(), [
            'group_id' => ['required', 'exists:groups,id'],
            'teacher_id' => ['required', 'exists:employees,id'],
            'week_start' => ['required', 'date'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'lesson_slot' => ['required', 'integer', Rule::in($slotKeys)],
            'schedule_subject_id' => ['required', 'exists:schedule_subjects,id'],
            'room' => ['nullable', 'string', 'max:64'],
            'building' => ['required', 'in:main,second,third'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                Toastr::error($message, 'Проверьте данные', ['progressBar' => true]);
            }

            return redirect()->back()->withInput();
        }

        $validated = $validator->validated();

        $weekMonday = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $subject = ScheduleSubject::findOrFail($validated['schedule_subject_id']);
        $times = ScheduleSlotTime::timesForSlot($config, (int) $validated['lesson_slot']);

        GroupScheduleEntry::create([
            'group_id' => $validated['group_id'],
            'teacher_id' => $validated['teacher_id'],
            'week_start_date' => $weekMonday->toDateString(),
            'weekday' => $validated['weekday'],
            'lesson_slot' => (int) $validated['lesson_slot'],
            'start_time' => $times['start_time'],
            'end_time' => $times['end_time'],
            'subject_title' => $subject->name,
            'schedule_subject_id' => $subject->id,
            'room' => $validated['room'] ?? null,
            'building' => $validated['building'],
        ]);

        Toastr::success('Занятие добавлено.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('schedule.constructor', array_filter([
            'week' => $weekMonday->toDateString(),
            'group_id' => $request->input('filter_group_id'),
        ]));
    }

    public function update(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $entry = GroupScheduleEntry::findOrFail($id);
        $config = ScheduleConstructorSetting::current();
        $slotKeys = array_keys(ScheduleSlotTime::slotOptions($config));

        $validator = Validator::make($request->all(), [
            'group_id' => ['required', 'exists:groups,id'],
            'teacher_id' => ['required', 'exists:employees,id'],
            'week_start' => ['required', 'date'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'lesson_slot' => ['required', 'integer', Rule::in($slotKeys)],
            'schedule_subject_id' => ['required', 'exists:schedule_subjects,id'],
            'room' => ['nullable', 'string', 'max:64'],
            'building' => ['required', 'in:main,second,third'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                Toastr::error($message, 'Проверьте данные', ['progressBar' => true]);
            }

            return redirect()->back()->withInput();
        }

        $validated = $validator->validated();

        $weekMonday = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $subject = ScheduleSubject::findOrFail($validated['schedule_subject_id']);
        $newSlot = (int) $validated['lesson_slot'];

        // Не пересчитывать время при смене глобальной сетки: если номер пары не меняли
        // (см. скрытое поле — для старых строк без lesson_slot это выбранная при открытии пара),
        // оставляем сохранённые start/end. Иначе — считаем по текущим настройкам.
        $anchorRaw = $request->input('entry_slot_anchor');
        $anchorSlot = ($anchorRaw === null || $anchorRaw === '') ? null : (int) $anchorRaw;
        if ($anchorSlot === null && $entry->lesson_slot !== null) {
            $anchorSlot = (int) $entry->lesson_slot;
        }
        $preserveTimes = $anchorSlot !== null && $anchorSlot === $newSlot;
        if ($preserveTimes) {
            $startTime = $entry->start_time;
            $endTime = $entry->end_time;
        } else {
            $times = ScheduleSlotTime::timesForSlot($config, $newSlot);
            $startTime = $times['start_time'];
            $endTime = $times['end_time'];
        }

        $entry->update([
            'group_id' => $validated['group_id'],
            'teacher_id' => $validated['teacher_id'],
            'week_start_date' => $weekMonday->toDateString(),
            'weekday' => $validated['weekday'],
            'lesson_slot' => $newSlot,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'subject_title' => $subject->name,
            'schedule_subject_id' => $subject->id,
            'room' => $validated['room'] ?? null,
            'building' => $validated['building'],
        ]);

        Toastr::success('Занятие обновлено.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('schedule.constructor', array_filter([
            'week' => $weekMonday->toDateString(),
            'group_id' => $request->input('filter_group_id'),
        ]));
    }

    public function delete(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $entry = GroupScheduleEntry::findOrFail($id);
        $week = $entry->week_start_date->toDateString();
        $entry->delete();

        Toastr::success('Занятие удалено.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('schedule.constructor', array_filter([
            'week' => $week,
            'group_id' => $request->input('group_id'),
        ]));
    }

    public function copyWeek(Request $request)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $validator = Validator::make($request->all(), [
            'week_start' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                Toastr::error($message, 'Проверьте данные', ['progressBar' => true]);
            }

            return redirect()->back();
        }

        $validated = $validator->validated();

        $sourceMonday = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $targetMonday = $sourceMonday->copy()->addWeek();

        $hasTarget = GroupScheduleEntry::whereDate('week_start_date', $targetMonday->toDateString())->exists();
        if ($hasTarget) {
            Toastr::error('В следующей неделе уже есть занятия. Сначала удалите или очистите ту неделю.', 'Нельзя скопировать', ['progressBar' => true]);

            return redirect()->back();
        }

        $sourceRows = GroupScheduleEntry::whereDate('week_start_date', $sourceMonday->toDateString())->get();
        if ($sourceRows->isEmpty()) {
            Toastr::error('В исходной неделе нет занятий для копирования.', 'Нет данных', ['progressBar' => true]);

            return redirect()->back();
        }

        foreach ($sourceRows as $row) {
            GroupScheduleEntry::create([
                'group_id' => $row->group_id,
                'teacher_id' => $row->teacher_id,
                'week_start_date' => $targetMonday->toDateString(),
                'weekday' => $row->weekday,
                'lesson_slot' => $row->lesson_slot,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'subject_title' => $row->subject_title,
                'schedule_subject_id' => $row->schedule_subject_id,
                'room' => $row->room,
                'building' => $row->building,
            ]);
        }

        Toastr::success(
            'Расписание скопировано на неделю с '.$targetMonday->format('d.m.Y').'.',
            'Успешно',
            ['progressBar' => true]
        );

        return redirect()->route('schedule.constructor', ['week' => $targetMonday->toDateString()]);
    }

    /**
     * Пересчитать start_time/end_time у занятий выбранной недели по текущим настройкам сетки
     * (номер пары из записи или, если пусто, по сохранённому времени начала).
     */
    public function recalculateWeek(Request $request)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        if ($request->filled('group_id')) {
            $request->merge(['group_id' => (int) $request->input('group_id')]);
        } else {
            $request->merge(['group_id' => null]);
        }

        $validator = Validator::make($request->all(), [
            'week_start' => ['required', 'date'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                Toastr::error($message, 'Проверьте данные', ['progressBar' => true]);
            }

            return redirect()->back();
        }

        $validated = $validator->validated();

        $weekMonday = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $groupId = $validated['group_id'] ?? null;

        $config = ScheduleConstructorSetting::current();
        $slotKeys = array_keys(ScheduleSlotTime::slotOptions($config));

        $query = GroupScheduleEntry::query()
            ->whereDate('week_start_date', $weekMonday->toDateString());
        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        $entries = $query->get();
        $updated = 0;
        $skipped = 0;

        foreach ($entries as $entry) {
            $slot = $entry->lesson_slot;
            if ($slot === null) {
                $slot = ScheduleSlotTime::inferSlotFromStart($config, (string) $entry->start_time);
            }
            if ($slot === null || ! in_array((int) $slot, $slotKeys, true)) {
                $skipped++;

                continue;
            }
            $slot = (int) $slot;
            $times = ScheduleSlotTime::timesForSlot($config, $slot);
            $entry->update([
                'lesson_slot' => $slot,
                'start_time' => $times['start_time'],
                'end_time' => $times['end_time'],
            ]);
            $updated++;
        }

        $message = 'Обновлено занятий: '.$updated.'.';
        if ($skipped > 0) {
            $message .= ' Пропущено (нет номера пары или пара вне текущей сетки): '.$skipped.'.';
        }

        Toastr::success($message, 'Пересчёт расписания', ['progressBar' => true]);

        return redirect()->route('schedule.constructor', array_filter([
            'week' => $weekMonday->toDateString(),
            'group_id' => $groupId,
        ]));
    }

    private function resolveMonday(?string $weekInput): Carbon
    {
        if ($weekInput) {
            return Carbon::parse($weekInput)->startOfWeek(Carbon::MONDAY);
        }

        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }
}
