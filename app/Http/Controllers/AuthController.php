<?php

namespace App\Http\Controllers;

use App\Http\Email\Email;
use App\Models\MailDeliveryFailure;
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
        Carbon::setLocale('ru');
        $time = Carbon::now()->translatedFormat('d F Y H:i');

        $employee = Employee::with(['role', 'group'])->findOrFail($user->id);

        $canTasks = $employee->canAccessPage('tasks');
        $canScheduleMy = $employee->canAccessPage('schedule_my');
        $canTeacherSchedule = $employee->canAccessPage('schedule_teacher');

        // Расписание группы (студент): право schedule_my или студент с группой.
        $studentish = $employee->canAccessPage('student_tests')
            || (optional($employee->role)->name === 'Студент');
        $canDashboardScheduleStudent = $canScheduleMy || ($studentish && (bool) $employee->group_id);

        $allowedViews = [];
        if ($canTasks) {
            $allowedViews[] = 'tasks';
        }
        if ($canDashboardScheduleStudent) {
            $allowedViews[] = 'schedule';
        }
        if ($canTeacherSchedule) {
            $allowedViews[] = 'schedule_teacher';
        }

        $showDashboardViewSwitcher = count($allowedViews) > 1;

        if ($allowedViews === []) {
            $dashboardMainBlock = 'none';
        } elseif (count($allowedViews) === 1) {
            $dashboardMainBlock = $allowedViews[0];
        } else {
            $requested = $request->query('view');
            if (is_string($requested) && in_array($requested, ['tasks', 'schedule', 'schedule_teacher'], true)) {
                $request->session()->put('dashboard_view', $requested);
            }
            $pref = $request->session()->get('dashboard_view', 'tasks');
            if (! in_array($pref, $allowedViews, true)) {
                $pref = $allowedViews[0];
            }
            $dashboardMainBlock = $pref;
        }

        $tasks = collect();
        if ($canTasks) {
            $tasks = Task::where('employees_id', $user->id)->get();
        }

        $notifs = Notifs::where('employee_id', $user->id)->get();

        $schedulePreview = collect();
        $scheduleTeacherPreview = collect();
        $scheduleWeekMonday = null;

        $needsScheduleWeek = ($canDashboardScheduleStudent && $dashboardMainBlock === 'schedule')
            || ($canTeacherSchedule && $dashboardMainBlock === 'schedule_teacher');
        if ($needsScheduleWeek) {
            $scheduleWeekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        }

        if ($canDashboardScheduleStudent && $dashboardMainBlock === 'schedule' && $scheduleWeekMonday && $employee->group_id) {
            $schedulePreview = GroupScheduleEntry::query()
                ->where('group_id', $employee->group_id)
                ->whereDate('week_start_date', $scheduleWeekMonday->toDateString())
                ->with(['teacher', 'scheduleSubject'])
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get();
        }

        if ($canTeacherSchedule && $dashboardMainBlock === 'schedule_teacher' && $scheduleWeekMonday) {
            $scheduleTeacherPreview = GroupScheduleEntry::query()
                ->where('teacher_id', $employee->id)
                ->whereDate('week_start_date', $scheduleWeekMonday->toDateString())
                ->with(['group', 'scheduleSubject'])
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get();
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
                'scheduleTeacherPreview',
                'scheduleWeekMonday',
                'employee',
                'showDashboardViewSwitcher',
                'canTasks',
                'canScheduleMy',
                'canTeacherSchedule',
                'canDashboardScheduleStudent',
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
                    if ($user->email_notifications ?? true) {
                        $email = new Email();
                        $email->send('Успешная авторизация в системе','Ваша авторизация в системе успешна. Желаем Вам продуктивной работы ;)',$user->email,$settings->title, [
                            'category' => MailDeliveryFailure::CATEGORY_AUTH,
                            'mail_type' => 'login_success',
                            'recipient_employee_id' => $user->id,
                            'recipient_name' => $user->fio,
                            'triggered_by_employee_id' => $user->id,
                        ]);
                    }
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
