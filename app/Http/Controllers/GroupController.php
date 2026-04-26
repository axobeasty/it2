<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Groups;
use App\Models\Roles;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        $groups = Groups::withCount('students')
            ->with(['students' => fn ($query) => $query->select('id', 'fio', 'group_id')->orderBy('fio')])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $students = Employee::whereHas('role', function ($query) {
            $query->where('name', 'Студент');
        })->orderBy('fio')->get();

        return view('groups.index', compact('user', 'settings', 'groups', 'students'));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Groups::create($validated);
        Toastr::success('Успешно', 'Группа создана', ["progressBar"=> true]);
        return redirect('/groups');
    }

    public function update(Request $request, int $id)
    {
        $group = Groups::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $group->update($validated);

        Toastr::success('Успешно', 'Группа обновлена', ["progressBar"=> true]);
        return redirect('/groups');
    }

    public function delete(int $id)
    {
        $group = Groups::findOrFail($id);
        Employee::where('group_id', $group->id)->update(['group_id' => null]);
        $group->delete();

        Toastr::success('Успешно', 'Группа удалена', ["progressBar"=> true]);
        return redirect('/groups');
    }

    public function assignStudents(Request $request, int $id)
    {
        $group = Groups::findOrFail($id);
        $studentIds = array_filter((array) $request->input('student_ids', []));

        if (empty($studentIds)) {
            Toastr::warning('Внимание', 'Выберите хотя бы одного студента', ["progressBar"=> true]);
            return redirect('/groups');
        }

        $studentRole = Roles::where('name', 'Студент')->first();
        if (!$studentRole) {
            Toastr::error('Ошибка', 'Роль "Студент" не найдена', ["progressBar"=> true]);
            return redirect('/groups');
        }

        Employee::whereIn('id', $studentIds)
            ->where('role_id', $studentRole->id)
            ->update(['group_id' => $group->id]);

        Toastr::success('Успешно', 'Студенты прикреплены к группе', ["progressBar"=> true]);
        return redirect('/groups');
    }

    public function detachStudent(int $id)
    {
        $student = Employee::findOrFail($id);
        $student->group_id = null;
        $student->save();

        Toastr::success('Успешно', 'Студент откреплен от группы', ["progressBar"=> true]);
        return redirect('/groups');
    }

    public function printStudents(Request $request, int $id)
    {
        $group = Groups::with(['students' => fn ($query) => $query->orderBy('fio')])
            ->findOrFail($id);
        $settings = Settings::where('id', 1)->first();

        return view('groups.print-students', compact('group', 'settings'));
    }
}
