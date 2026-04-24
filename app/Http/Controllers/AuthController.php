<?php

namespace App\Http\Controllers;

use App\Http\Email\Email;
use App\Models\Employee;
use App\Models\GroupScheduleEntry;
use App\Models\Notifs;
use App\Models\Settings;
use App\Models\Task;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Log;
use Nutgram\Laravel\Facades\Telegram;
use PHPMailer\PHPMailer\PHPMailer;
use function Laravel\Prompts\error;
use Jenssegers\Date\Date;


class AuthController extends Controller
{
    public function teacher(Request $request)
    {
        $settings = Settings::where('id',1)->first();
        if(!$request->session()->has('user')){
            return redirect('/');
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('faculties_manage') && ! $user->canAccessPage('chairs_manage')) {
            Toastr::error('У вас нет доступа к разделу преподавателя (факультеты и кафедры).', 'Ошибка доступа', ['progressBar' => true]);
            return redirect('/');
        }
        return view('teachers.index', compact('user','settings'));
    }

    public function student(Request $request)
    {
        $settings = Settings::where('id',1)->first();
        if(!$request->session()->has('user')){
            return redirect('/');
        }

        $user = $request->session()->get('user');
        if (
            ! $user->canAccessPage('dashboard')
            && ! $user->canAccessPage('schedule_my')
            && ! $user->canAccessPage('student_tests')
        ) {
            Toastr::error('У вас нет доступа к разделу студента.', 'Ошибка доступа', ['progressBar' => true]);
            return redirect('/');
        }
        return view('students.index', compact('user','settings'));
    }

    public function dashboard(Request $request)
    {
        $settings = Settings::where('id',1)->first();
        if(!$request->session()->has('user')){
            return redirect('/');
        }

        $user = $request->session()->get('user');
        $notifs = Notifs::where('employee_id', $user->id)->get();
        $tasks = Task::where('employees_id', $user->id)->get();
        Carbon::setLocale('ru');
        $time = Carbon::now()->translatedFormat('d F Y H:i');

        $employee = Employee::with(['role', 'group'])->findOrFail($user->id);

        $dashboardMainBlock = 'tasks';
        if (! $employee->canAccessPage('tasks')) {
            if ($employee->role && $employee->role->name === 'Студент' && $employee->canAccessPage('schedule_my')) {
                $dashboardMainBlock = 'schedule';
            } else {
                $dashboardMainBlock = 'none';
            }
        }

        $schedulePreview = collect();
        $scheduleWeekMonday = null;
        if ($dashboardMainBlock === 'schedule') {
            $scheduleWeekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
            if ($employee->group_id) {
                $schedulePreview = GroupScheduleEntry::query()
                    ->where('group_id', $employee->group_id)
                    ->whereDate('week_start_date', $scheduleWeekMonday->toDateString())
                    ->with(['teacher', 'scheduleSubject'])
                    ->orderBy('weekday')
                    ->orderBy('start_time')
                    ->get();
            }
        }

        if($settings->is_enabled == 1 || $user->canAccessPage('maintenance_bypass')){
            return view('dashboard.dashboard', compact(
                'user',
                'settings',
                'tasks',
                'time',
                'notifs',
                'dashboardMainBlock',
                'schedulePreview',
                'scheduleWeekMonday',
                'employee',
            ));
        }

        return view('settings.disabled',compact('user','settings','tasks'));
    }

    public function test(Request $request){
        $axo = Employee::where('login','axobeast')->first();
        return dump($axo);
    }
    public function auth(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            if($settings->is_enabled == 1 || $user->canAccessPage('maintenance_bypass')){
                return redirect('/dashboard');
            }
            $tasks = Task::where('employees_id',$user->id)->get();
            return view('settings.disabled',compact('user','settings','tasks'));

        }else{
            return view('index',compact('settings'));
        }

    }
    public function notallowed(){
        Toastr::warning('Неверный запрос', 'Проверьте Ваш запрос', ["progressBar"=> true]);
        return redirect('/');
    }
    public function add_task(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            if($settings->is_enabled == 1 || $user->canAccessPage('maintenance_bypass')){

                $task = new Task;
                $task->title = $request->input('title');
                $task->description = $request->input('description');
                $task->priority = $request->input('priority');
                $task->employees_id = $user->id;
                $task->save();
                Toastr::success('Успешно', 'Задача успешно добавлена', ["progressBar"=> true]);
                return redirect('/dashboard');
            }else{
                return view('settings.disabled',compact('user','settings'));
            }

        }else{
            return view('index',compact('settings'));
        }
    }
    public function login(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $request->session()->forget('user');
            $login  = $request->input('login');
            $password = $request->input('password');
            $user = Employee::where('login',$login)->first();
            if($user->active == 1){
                if(Hash::check($password,$user->password)){
                    $request->session()->put('user',$user);
                    Toastr::success('Успешно', 'Авторизация прошла успешно!', ["progressBar"=> true]);
                    $email = new Email();
                    return redirect('/dashboard');
                }else{
                    Toastr::error('Ошибка авторизации', 'Логин или пароль введены неверно!', ["progressBar"=> true]);
                    return view('index',compact('settings'));
                }
            }else{
                Toastr::error('Ошибка авторизации', 'Ваш аккаунт диактивирован. Обратитесь к системному администратору!', ["progressBar"=> true]);
                return view('index',compact('settings'));
            }

        }else{
            $login  = $request->input('login');
            $password = $request->input('password');
            $user = Employee::where('login',$login)->first();

            if($user != null){
                if($user->active == 1){
                if(Hash::check($password,$user->password)){
                    $request->session()->put('user',$user);
                    Toastr::success('Успешно', 'Авторизация прошла успешно!', ["progressBar"=> true]);
                    $email = new Email();
                    $email->send('Успешная авторизация в системе','Ваша авторизация в системе успешна. Желаем Вам продуктивной работы ;)',$user->email,$settings->title);
                    return redirect('/dashboard');
                }else{
                    Toastr::error('Ошибка авторизации', 'Логин или пароль введены неверно!', ["progressBar"=> true]);
                    return view('index',compact('settings'));
                }
            }else{
                    Toastr::error('Ошибка авторизации', 'Ваш аккаунт диактивирован. Обратитесь к системному администратору!', ["progressBar"=> true]);
                    return view('index',compact('settings'));
                }
            }else{
                Toastr::error('Ошибка авторизации', 'Пользователь не найден!', ["progressBar"=> true]);
                return redirect('/');
            }
           // return dump($login);


        }

    }
    public function logout(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $request->session()->forget('user');
        }else{
            return view('index',compact('settings'));
        }
        return redirect('/');
    }
}
