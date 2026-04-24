<?php

namespace App\Http\Controllers;

use App\Models\ScheduleConstructorSetting;
use App\Models\ScheduleSubject;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleConstructorSettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id', 1)->first();

        if (! $user->canAccessPage('schedule_constructor_settings')) {
            Toastr::error('Нет доступа к настройкам расписания.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $config = ScheduleConstructorSetting::current();
        $subjects = ScheduleSubject::orderBy('name')->get();

        return view('schedule.constructor_settings', [
            'user' => $user,
            'settings' => $settings,
            'config' => $config,
            'subjects' => $subjects,
            'durationOptions' => ScheduleConstructorSetting::DURATION_OPTIONS,
        ]);
    }

    public function save(Request $request)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor_settings')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $validator = Validator::make($request->all(), [
            'first_lesson_start' => ['required', 'date_format:H:i'],
            'lesson_duration_minutes' => ['required', 'integer', 'in:40,45,90'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:45'],
            'max_slots_per_day' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                Toastr::error($message, 'Проверьте данные', ['progressBar' => true]);
            }

            return redirect()->back()->withInput();
        }

        $validated = $validator->validated();

        $config = ScheduleConstructorSetting::current();
        $config->update([
            'first_lesson_start' => \Carbon\Carbon::parse($validated['first_lesson_start'])->format('H:i:s'),
            'lesson_duration_minutes' => $validated['lesson_duration_minutes'],
            'break_minutes' => $validated['break_minutes'],
            'max_slots_per_day' => $validated['max_slots_per_day'],
        ]);

        Toastr::success('Настройки конструктора сохранены.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('schedule.constructor.settings');
    }

    public function storeSubject(Request $request)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor_settings')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                Toastr::error($message, 'Проверьте данные', ['progressBar' => true]);
            }

            return redirect()->back()->withInput();
        }

        $validated = $validator->validated();

        ScheduleSubject::create(['name' => trim($validated['name'])]);

        Toastr::success('Предмет добавлен.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('schedule.constructor.settings');
    }

    public function deleteSubject(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        if (! $user->canAccessPage('schedule_constructor_settings')) {
            Toastr::error('У вас недостаточно прав для этого действия.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $subject = ScheduleSubject::query()->find($id);
        if (! $subject) {
            Toastr::error('Предмет не найден или уже удалён.', 'Ошибка', ['progressBar' => true]);

            return redirect()->route('schedule.constructor.settings');
        }

        try {
            $subject->delete();
        } catch (\Throwable $e) {
            Toastr::error('Не удалось удалить предмет. Попробуйте позже.', 'Ошибка', ['progressBar' => true]);

            return redirect()->route('schedule.constructor.settings');
        }

        Toastr::success('Предмет удалён.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('schedule.constructor.settings');
    }
}
