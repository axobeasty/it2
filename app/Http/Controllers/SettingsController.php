<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Support\DatabaseProfileManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly DatabaseProfileManager $databaseProfileManager)
    {
    }

    public function index(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.general',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function authenticate(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.authenticate',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function general(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.general',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function database(Request $request)
    {
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.database',[
                    'user' => $user,
                    'settings' => $settings,
                    'activeProfile' => env('DB_ACTIVE_PROFILE', 'sqlite'),
                    'remoteHost' => env('DB_REMOTE_HOST', ''),
                    'remotePort' => env('DB_REMOTE_PORT', '3306'),
                    'remoteDatabase' => env('DB_REMOTE_DATABASE', ''),
                    'remoteUsername' => env('DB_REMOTE_USERNAME', ''),
                    'remotePassword' => env('DB_REMOTE_PASSWORD', ''),
                    'remoteCharset' => env('DB_REMOTE_CHARSET', 'utf8mb4'),
                    'remoteCollation' => env('DB_REMOTE_COLLATION', 'utf8mb4_unicode_ci'),
                ]);
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function disable(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                $settings->is_enabled = 0;
                $settings->save();
                Toastr::success('Техническое обслуживание', 'Сайт успешно выключен!', ["progressBar"=> true]);
                return redirect('/settings/general');
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function save(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();

            if($user->canAccessPage('settings')){
                if($request->input('page') == 'authenticate'){
                    $settings->auth_mode = $request->input('auth_method');


                    $settings->save();
                }
                else if($request->input('page') == 'general'){
                    $settings->title = $request->input('title');
                    $settings->disable_reason = $request->input('disable_reason');

                    $settings->save();
                }else if($request->input('page') == 'email'){
                    $settings->email_enabled = $request->input('email_enabled');
                    $settings->save();
                }else if($request->input('page') == 'database'){
                    $validated = $request->validate([
                        'db_profile' => ['required', 'in:sqlite,remote'],
                        'remote_host' => ['nullable', 'string', 'max:255'],
                        'remote_port' => ['nullable', 'string', 'max:10'],
                        'remote_database' => ['nullable', 'string', 'max:255'],
                        'remote_username' => ['nullable', 'string', 'max:255'],
                        'remote_password' => ['nullable', 'string', 'max:255'],
                        'remote_charset' => ['nullable', 'string', 'max:64'],
                        'remote_collation' => ['nullable', 'string', 'max:64'],
                    ]);

                    if ($validated['db_profile'] === 'remote' && empty($validated['remote_host'])) {
                        Toastr::error('Для remote профиля укажите host удаленной БД.', 'Ошибка валидации', ["progressBar"=> true]);
                        return redirect('/settings/database');
                    }

                    $this->databaseProfileManager->applyDatabaseSettings([
                        'db_profile' => $validated['db_profile'],
                        'remote_host' => (string) ($validated['remote_host'] ?? ''),
                        'remote_port' => (string) ($validated['remote_port'] ?? '3306'),
                        'remote_database' => (string) ($validated['remote_database'] ?? ''),
                        'remote_username' => (string) ($validated['remote_username'] ?? ''),
                        'remote_password' => (string) ($validated['remote_password'] ?? ''),
                        'remote_charset' => (string) ($validated['remote_charset'] ?? 'utf8mb4'),
                        'remote_collation' => (string) ($validated['remote_collation'] ?? 'utf8mb4_unicode_ci'),
                    ]);
                }


                Toastr::success('Успешное сохранение', 'Настройки сайта успешно сохранены', ["progressBar"=> true]);
                return redirect('/settings/'.$request->input('page'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function enable(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                $settings->is_enabled = 1;
                $settings->save();
                Toastr::success('Техническое обслуживание', 'Сайт успешно включен!', ["progressBar"=> true]);
                return redirect('/settings/general');
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function migrateDatabase(Request $request)
    {
        if (! $request->session()->has('user')) {
            return redirect('/');
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $validated = $request->validate([
            'source_profile' => ['required', 'in:sqlite,remote'],
            'target_profile' => ['required', 'in:sqlite,remote'],
        ]);

        if ($validated['source_profile'] === $validated['target_profile']) {
            Toastr::error('Источник и назначение должны отличаться.', 'Ошибка', ["progressBar"=> true]);
            return redirect('/settings/database');
        }

        try {
            $this->databaseProfileManager->migrateBetweenProfiles(
                $validated['source_profile'],
                $validated['target_profile']
            );
            Toastr::success('Перенос данных завершен успешно.', 'Готово', ["progressBar"=> true]);
        } catch (\Throwable $e) {
            Toastr::error('Не удалось перенести данные: '.$e->getMessage(), 'Ошибка', ["progressBar"=> true]);
        }

        return redirect('/settings/database');
    }
    public function notallowed(){
        return redirect('/');
    }

    public function email(Request $request)
    {
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.email',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
        return redirect('/');
    }
    }
}
