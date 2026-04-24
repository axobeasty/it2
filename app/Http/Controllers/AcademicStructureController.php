<?php

namespace App\Http\Controllers;

use App\Models\Chair;
use App\Models\Faculty;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class AcademicStructureController extends Controller
{
    public function faculties(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        $faculties = Faculty::with(['chairs' => function ($query) {
            $query->orderBy('name');
        }])->withCount('chairs')->orderBy('name')->get();

        return view('teachers.faculties', compact('user', 'settings', 'faculties'));
    }

    public function facultiesCreate(Request $request)
    {
        Faculty::create([
            'name' => trim((string) $request->input('name')),
        ]);
        Toastr::success('Успешно', 'Факультет создан', ["progressBar"=> true]);
        return redirect('/teachers/faculties');
    }

    public function facultiesEdit(Request $request, int $id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->name = trim((string) $request->input('name'));
        $faculty->save();
        Toastr::success('Успешно', 'Факультет обновлен', ["progressBar"=> true]);
        return redirect('/teachers/faculties');
    }

    public function facultiesDelete(int $id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->delete();
        Toastr::success('Успешно', 'Факультет удален', ["progressBar"=> true]);
        return redirect('/teachers/faculties');
    }

    public function chairs(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        $faculties = Faculty::orderBy('name')->get();
        $chairs = Chair::with('faculty')->orderBy('name')->get();

        return view('teachers.chairs', compact('user', 'settings', 'chairs', 'faculties'));
    }

    public function chairsCreate(Request $request)
    {
        Chair::create([
            'name' => trim((string) $request->input('name')),
            'faculty_id' => $request->input('faculty_id') ?: null,
        ]);
        Toastr::success('Успешно', 'Кафедра создана', ["progressBar"=> true]);
        return redirect('/teachers/chairs');
    }

    public function chairsEdit(Request $request, int $id)
    {
        $chair = Chair::findOrFail($id);
        $chair->name = trim((string) $request->input('name'));
        $chair->faculty_id = $request->input('faculty_id') ?: null;
        $chair->save();
        Toastr::success('Успешно', 'Кафедра обновлена', ["progressBar"=> true]);
        return redirect('/teachers/chairs');
    }

    public function chairsDelete(int $id)
    {
        $chair = Chair::findOrFail($id);
        $chair->delete();
        Toastr::success('Успешно', 'Кафедра удалена', ["progressBar"=> true]);
        return redirect('/teachers/chairs');
    }
}
