<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Support\PageAccess;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = \App\Models\Settings::where('id', 1)->first();

        $roles = Roles::with('pagePermissions')->orderBy('name')->get();
        $pages = PageAccess::allLabels();
        $groupedPages = PageAccess::groupedLabelsForRoles();

        return view('Employees.roles', compact('user', 'settings', 'roles', 'pages', 'groupedPages'));
    }

    public function create(Request $request)
    {
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            Toastr::error('Ошибка', 'Название роли не может быть пустым!', ["progressBar"=> true]);
            return redirect()->back();
        }

        $role = Roles::create([
            'name' => $name,
            'is_system' => false,
        ]);

        $this->syncPermissions($role, (array) $request->input('permissions', []));

        Toastr::success('Успешно', 'Кастомная роль создана', ["progressBar"=> true]);
        return redirect('/roles');
    }

    public function update(Request $request, int $id)
    {
        $role = Roles::findOrFail($id);
        if (! (bool) $role->is_system) {
            $role->name = trim((string) $request->input('name', $role->name));
        }
        $role->save();

        $this->syncPermissions($role, (array) $request->input('permissions', []));

        Toastr::success('Успешно', 'Роль обновлена', ["progressBar"=> true]);
        return redirect('/roles');
    }

    public function delete(int $id)
    {
        $role = Roles::findOrFail($id);
        if ((bool) $role->is_system) {
            Toastr::error('Ошибка', 'Системную роль нельзя удалить!', ["progressBar"=> true]);
            return redirect('/roles');
        }

        if ($role->employees()->exists()) {
            Toastr::error('Ошибка', 'Нельзя удалить роль, к которой привязаны пользователи.', ["progressBar"=> true]);
            return redirect('/roles');
        }

        $role->delete();
        Toastr::success('Успешно', 'Роль удалена', ["progressBar"=> true]);
        return redirect('/roles');
    }

    private function syncPermissions(Roles $role, array $permissions): void
    {
        $allowedKeys = array_keys(PageAccess::allLabels());
        $filtered = array_values(array_intersect($permissions, $allowedKeys));

        $role->pagePermissions()->delete();
        foreach ($filtered as $pageKey) {
            $role->pagePermissions()->create(['page_key' => $pageKey]);
        }
    }
}
