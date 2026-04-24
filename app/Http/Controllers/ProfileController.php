<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioRoles;
use App\Models\PortfolioTypes;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request){
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        $portfolios = Portfolio::all();
        $portfolioTypes = portfolioTypes::all();

        if($user){
            return view('dashboard.profile', compact('user', 'settings', 'portfolios','portfolioTypes'));
        }else{
            return redirect('/');
         }
     }

    public function p_add(Request $request)
    {

        $user = $request->session()->get('user');

        // Проверяем, авторизован ли пользователь
        if (!$request->session()->has('user')) {
            Toastr::error('Ошибка доступа', 'Вы не авторизованы', ["progressBar" => true]);
            return redirect('/'); // Важно: редирект!
        }
        if (! $user->canAccessPage('portfolio')) {
            Toastr::error('Нет доступа к добавлению в портфолио.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }
            $type = PortfolioTypes::findOrFail($request->type_id);

            // Сохраняем файл
            $filePath = $request->file('file')->store('uploads/portfolios', 'public');
            // Генерируем номер
            $new = new Portfolio();
            $new -> number=$user->id . '-' . now()->format('His');;
            $new -> status=0;
            $new -> type_id =$request->input("type_id");
            $new -> title =$request-> input ("title") ;
            $new -> file_path =$filePath ;
            $new->employee_id = $user->id;
            $new->role_id = 1;
            $new->save();
            // Создаём запись
            Toastr::success('Успешно', 'Запись добавлена в портфолио!', ["progressBar" => true]);
            return redirect()->back();
       // Добавлен возврат
    }

    public function stypes(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        if ($request->session()->has('user')) {
            if (! $user->canAccessPage('portfolio_types')) {
                Toastr::error('Нет доступа к типам портфолио.', 'Ошибка доступа', ['progressBar' => true]);

                return redirect('/dashboard');
            }
            $types = PortfolioTypes::all();
            return view('dashboard.types', compact('user', 'settings','types'));
        }else{
            return redirect('/');
        }

    }

    public function sroles(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        if ($request->session()->has('user')) {
            if (! $user->canAccessPage('portfolio_types')) {
                Toastr::error('Нет доступа к ролям портфолио.', 'Ошибка доступа', ['progressBar' => true]);

                return redirect('/dashboard');
            }
            $roles = PortfolioRoles::all();
            return view('dashboard.roles', compact('user', 'settings','roles'));
        }else{
            return redirect('/');
        }
    }

    /**
     * Отдаёт вложение портфолио из диска public без обхода через symlink public/storage.
     */
    public function portfolioFile(Request $request, Portfolio $portfolio)
    {
        $user = $request->session()->get('user');
        if (! $user) {
            Toastr::error('Войдите в систему, чтобы скачать файл.', 'Ошибка доступа', ['progressBar' => true]);
            return redirect('/');
        }

        $isOwner = (int) $portfolio->employee_id === (int) $user->id;
        $canModerate = $user->canAccessPage('portfolio_confirm');
        $canSettings = $user->canAccessPage('settings');

        if (! $isOwner && ! $canModerate && ! $canSettings) {
            Toastr::error('У вас нет доступа к этому файлу.', 'Ошибка доступа', ['progressBar' => true]);
            return redirect('/');
        }

        if (! $portfolio->file_path) {
            Toastr::error('Файл для этой записи не найден.', 'Ошибка', ['progressBar' => true]);
            return redirect()->back();
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($portfolio->file_path)) {
            Toastr::error('Файл отсутствует на сервере.', 'Ошибка', ['progressBar' => true]);
            return redirect()->back();
        }

        return $disk->response($portfolio->file_path);
    }

    public function p_show(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        if ($request->session()->has('user')) {
            if (! $user->canAccessPage('portfolio')) {
                Toastr::error('Нет доступа к портфолио.', 'Ошибка доступа', ['progressBar' => true]);

                return redirect('/dashboard');
            }
            $portfolios = Portfolio::all();
            $portfolioTypes = portfolioTypes::all();
            return view('dashboard.portfolio', compact('user', 'settings', 'portfolios','portfolioTypes'));
        }else{
            return redirect('/');
        }
    }

    public function type_add(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id',1)->first();
        if ($request->session()->has('user')) {
            if (! $user->canAccessPage('portfolio_types')) {
                Toastr::error('Нет доступа.', 'Ошибка доступа', ['progressBar' => true]);

                return redirect('/dashboard');
            }
            $new = new PortfolioTypes();
            $new->name = $request->input('name');
            $new->save();
            Toastr::success('Успешно', 'Тип портфолио успешно создан!', ["progressBar" => true]);
            return redirect()->back();
        }else{
            return redirect('/');
        }
    }

    public function type_edit(string $id, Request $request)
    {

    }

    public function type_delete(string $id,Request $request)
    {
        $user = $request->session()->get('user');
        if (! $request->session()->has('user')) {
            return redirect('/');
        }
        if (! $user->canAccessPage('portfolio_types')) {
            Toastr::error('У Вас недостаточно прав для этого действия!', 'Ошибка', ["progressBar" => true]);

            return redirect()->back();
        }
        $type = PortfolioTypes::findOrFail((int) $id);
        $type->delete();
        Toastr::success('Успешно', 'Тип удалён', ["progressBar" => true]);

        return redirect()->back();
    }
}
