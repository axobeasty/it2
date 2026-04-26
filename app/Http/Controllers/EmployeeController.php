<?php

namespace App\Http\Controllers;

use App\Http\Email\Email;
use App\Models\MailDeliveryFailure;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Groups;
use App\Models\Faculty;
use App\Models\Chair;
use App\Models\Roles;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
   public function index(Request $request){

       if($request->session()->has('user')){
           $user = $request->session()->get('user');
           $settings = Settings::where('id',1)->first();
           $departments = Department::all();
           $employees = Employee::with(['department:id,title', 'role:id,name'])
               ->orderBy('fio')
               ->paginate(25)
               ->withQueryString();
           $roles = Roles::all();
           $groups = Groups::orderBy('name')->get();
           $faculties = Faculty::orderBy('name')->get();
           $chairs = Chair::with('faculty')->orderBy('name')->get();
           $studentRole = Roles::where('name', 'Студент')->first();
           $studentRoleId = $studentRole?->id;
           $citizenships = $this->citizenships();
           if (! $user->canAccessPage('employees_manage')) {
               Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
               return redirect('/');
           }
           if($settings->is_enabled == 1 || $user->canAccessPage('maintenance_bypass')){
               return view('Employees.index', compact('user', 'employees','settings','departments','roles','groups','studentRoleId','citizenships','faculties','chairs'));
           }else{
               return view('settings.disabled',compact('user','settings'));
           }

       }else{
            return redirect('/');
       }

   }
    public function notallowed(){
        Toastr::warning('Неверный запрос', 'Проверьте Ваш запрос', ["progressBar"=> true]);
        return redirect('/employees');
    }
   public function newEmployee(Request $request){
       $settings = Settings::where('id',1)->first();
       if($request->session()->has('user')){
           $user = $request->session()->get('user');
           if($user->canAccessPage('employees_manage')){
               $data[] = $request->input();
               if(Employee::where('email',$data[0]["email"])->exists()){
                   Toastr::error('Сотрудник с указанной почтой уже существует!', 'Ошибка создания профиля сотрудника.', ["progressBar"=> true]);
               }else{
                   $new = new Employee();
                   $new->login = $data[0]['login'];
                   $new->fio = $data[0]['fio'];
                   $new->email =$data[0]["email"];
                   $new->role_id = (int) $request->input('role_id', 4);
                   $psw = Str::password($length = 8, $letters = true, $numbers = true, $symbols = true, $spaces = false);
                   $new->password = Hash::make($psw);
                   $new->room = $data[0]['room'];
                   $new->department_id = $data[0]['department'];
                   $new->active= 0;
                   $this->applyStudentFields($new, $request);
                   $new->save();
                   $email = new Email();
                   $hash = Crypt::encryptString($new->id.$new->login.$new->email);
                   $email->send('Вам создали профиль сотрудника.','Здравствуйте, '.$new->fio.'</b></p>! Технический специалист '.$settings->title.' создал для вам профиль сотрудника в системе. Для начала Вам необходимо активировать свой профиль, перейдя по ссылке:  <a href="http://'.$request->getHost().':'.$request->getPort().'/employees/'.$new->id.'/activate/'.$hash.'">активировать профиль</a>
</br>Ваши данные:</br> логин:<strong>'.$new->login .'</strong>, пароль:<i>'. $psw.'
' , $new->email,$settings->title, [
                       'category' => MailDeliveryFailure::CATEGORY_EMPLOYEES,
                       'mail_type' => 'employee_invite',
                       'recipient_employee_id' => $new->id,
                       'recipient_name' => $new->fio,
                       'triggered_by_employee_id' => $user->id,
                   ]);
                   Toastr::success('Профиль сотрудника успешно создан!', 'Успешно.', ["progressBar"=> true]);


               }
               return redirect()->back();

           }else{
               Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
               return redirect('/employees');
           }

       }else{
           return redirect('/');
       }

   }
   public function edit(Request $request,$id){
        if($request->session()->has('user')){
            $settings = Settings::where('id',1)->first();
            $user = $request->session()->get('user');
            if($user->canAccessPage('employees_manage')){
                $editer = Employee::where('id',$id)->first();
                if($request->session()->get('user')['login'] == $editer->login){
                    $request->session()->forget('user');
                    $editer->login = $request->input('login');
                    $editer->fio = $request->input('fio');
                    if(!Employee::where('email','=',$request->input('email'))->exists()){
                        $editer->email = $request->input('email');
                        $email = new Email();
                        $email->send('Изменение данных аккаунта','Для Вашего аккаунта была изменена почта на '.$request->input('email'). '</br><span class="text-danger">Внимание, если вы не просили смены почты - свяжитель с техническим администратором!</span>',$request->input('email'),$settings->title, [
                            'category' => MailDeliveryFailure::CATEGORY_EMPLOYEES,
                            'mail_type' => 'employee_email_change',
                            'recipient_employee_id' => $editer->id,
                            'recipient_name' => $editer->fio,
                            'triggered_by_employee_id' => $user->id,
                        ]);
                    }else{
                        Toastr::error('Почта '.$request->input('email'). ' уже привязана к пользователю.', 'Не удалось обновить почту пользователя. Проверьте правильность написания указанной почты.', ["progressBar"=> true]);
                    }

                    $editer->department_id=$request->input("department_id");
                    $editer->role_id = (int) $request->input("role_id", $editer->role_id);
                    if($request->input('password') != null){
                        $editer->password = Hash::make($request->input('password'));
                        $email = new Email();
                        $email->send('Изменение данных аккаунта','Для Вашего аккаунта был изменен пароль администратором системы. Новый пароль: '.$request->input('password'),$request->input('email'),$settings->title, [
                            'category' => MailDeliveryFailure::CATEGORY_EMPLOYEES,
                            'mail_type' => 'employee_password_change',
                            'recipient_employee_id' => $editer->id,
                            'recipient_name' => $editer->fio,
                            'triggered_by_employee_id' => $user->id,
                        ]);
                    }
                    $editer->room = $request->input('room');
                    $editer->active = $request->boolean('active') ? 1 : 0;
                    $this->applyStudentFields($editer, $request);
                    $editer->save();
                    Toastr::success('Успешно', 'Профиль успешно изменен', ["progressBar"=> true]);
                    Toastr::warning('Внимание!', 'Вы изменили собственный профиль, поэтому Вам необходимо авторизоваться повторно!', ["progressBar"=> true]);
                    return redirect('/');
                }else{
                    $editer->login = $request->input('login');
                    $editer->fio = $request->input('fio');
                    if(!Employee::where('email','=',$request->input('email'))->exists()){
                        $editer->email = $request->input('email');
                        $email = new Email();
                        $email->send('Изменение данных аккаунта','Для Вашего аккаунта была изменена почта на '.$request->input('email'). '</br><span class="text-danger">Внимание, если вы не просили смены почты - свяжитель с техническим администратором!</span>',$request->input('email'),$settings->title, [
                            'category' => MailDeliveryFailure::CATEGORY_EMPLOYEES,
                            'mail_type' => 'employee_email_change',
                            'recipient_employee_id' => $editer->id,
                            'recipient_name' => $editer->fio,
                            'triggered_by_employee_id' => $user->id,
                        ]);
                    }else{
                        Toastr::error('Почта '.$request->input('email'). ' уже привязана к пользователю.', 'Не удалось обновить почту пользователя. Проверьте правильность написания указанной почты.', ["progressBar"=> true]);
                    }
                    $editer->department_id=$request->input("department_id");
                    $editer->role_id = (int) $request->input("role_id", $editer->role_id);
                    if($request->input('password') != null){
                        $editer->password = Hash::make($request->input('password'));
                        $email = new Email();
                        $email->send('Изменение данных аккаунта','Для Вашего аккаунта был изменен пароль администратором системы. Новый пароль: '.$request->input('password'),$request->input('email'),$settings->title, [
                            'category' => MailDeliveryFailure::CATEGORY_EMPLOYEES,
                            'mail_type' => 'employee_password_change',
                            'recipient_employee_id' => $editer->id,
                            'recipient_name' => $editer->fio,
                            'triggered_by_employee_id' => $user->id,
                        ]);
                    }
                    $editer->room = $request->input('room');
                    $editer->active = $request->boolean('active') ? 1 : 0;
                    $this->applyStudentFields($editer, $request);
                    $editer->save();
                    Toastr::success('Успешно', 'Профиль успешно изменен', ["progressBar"=> true]);

                    return redirect()->back();
                }
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/employees');
            }


        }else{
            return redirect('/');
        }
   }
   public function deactivate(Request $request,$id){
       if($request->session()->has('user')){
           $user = $request->session()->get('user');
           if($user->canAccessPage('employees_manage')){
               $editor = Employee::where('id',$id)->first();
               $editor->active = 0;
               $editor->save();
               Toastr::success('Успешно', 'Профиль успешно диактивирован', ["progressBar"=> true]);
               return redirect()->back();
           }else{
               Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
               return redirect('/employees');
           }
       }else{
           return redirect('/');
       }
   }
    public function activate(Request $request,$id){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            if($user->canAccessPage('employees_manage')){
                $editor = Employee::where('id',$id)->first();
                $editor->active = 1;
                $editor->save();
                Toastr::success('Успешно', 'Профиль успешно активирован', ["progressBar"=> true]);
                return redirect()->back();
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/employees');
            }
        }else{
            return redirect('/');
        }
    }
   public function delete(Request $request,$id){
       if($request->session()->has('user')){
           $user = $request->session()->get('user');
           if($user->canAccessPage('employees_manage')){
               $del = Employee::where('id',$id)->delete();
               return redirect()->back();
           }else{
               Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
               return redirect('/employees');
           }

       }else{
           return redirect('/');
       }
   }

    public function activateEmployee(string $id,string $code,Request $request)
    {
        if($request->session()->has('user')){
            Toastr::error('Вы уже авторизованы', 'Активация профиля.', ["progressBar"=> true]);
            return redirect('/');
        }else{
        $profile = Employee::where('id',$id)->first();
        if($profile->active == 0){

            if($profile->id.$profile->login.$profile->email === Crypt::decryptString($code)){
                $profile->active = 1;
                $profile->save();
                Toastr::success('Ваш профиль успешно активирован.', 'Активация профиля.', ["progressBar"=> true]);
                return redirect('/');
            }else{
                Toastr::error('Недействительная ссылка', 'Активация профиля.', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            Toastr::info('Указанный профиль уже активирован. Вы можете войти в свой аккаунт', 'Активация профиля.', ["progressBar"=> true]);
            return redirect('/');
        }


    }
    }

    private function applyStudentFields(Employee $employee, Request $request): void
    {
        $studentRoleId = Roles::where('name', 'Студент')->value('id');
        if ((int) $employee->role_id === (int) $studentRoleId) {
            $groupId = $request->input('group_id');
            $newGroupName = trim((string) $request->input('new_group_name', ''));
            if ($newGroupName !== '') {
                $group = Groups::firstOrCreate(['name' => $newGroupName], ['description' => null]);
                $groupId = $group->id;
            }

            $employee->group_id = $groupId ?: null;
            $employee->faculty_id = $request->input('faculty_id') ?: null;
            $employee->chair_id = $request->input('chair_id') ?: null;
            $employee->course = $request->input('course');
            $employee->record_book_number = $request->input('record_book_number');
            $employee->faculty = optional(Faculty::find($employee->faculty_id))->name;
            $employee->department_name = optional(Chair::find($employee->chair_id))->name;
            $employee->birth_date = $request->input('birth_date');
            $employee->citizenship = $request->input('citizenship');
            $employee->phone = $request->input('phone');
            $employee->enrollment_year = $request->input('enrollment_year');
            return;
        }

        $employee->group_id = null;
        $employee->faculty_id = null;
        $employee->chair_id = null;
        $employee->course = null;
        $employee->record_book_number = null;
        $employee->faculty = null;
        $employee->department_name = null;
        $employee->birth_date = null;
        $employee->citizenship = null;
        $employee->phone = null;
        $employee->enrollment_year = null;
    }

    private function citizenships(): array
    {
        return [
            ['flag' => '🇷🇺', 'name' => 'Россия'],
            ['flag' => '🇧🇾', 'name' => 'Беларусь'],
            ['flag' => '🇰🇿', 'name' => 'Казахстан'],
            ['flag' => '🇦🇲', 'name' => 'Армения'],
            ['flag' => '🇰🇬', 'name' => 'Киргизия'],
            ['flag' => '🇺🇿', 'name' => 'Узбекистан'],
            ['flag' => '🇹🇯', 'name' => 'Таджикистан'],
            ['flag' => '🇦🇿', 'name' => 'Азербайджан'],
            ['flag' => '🇲🇩', 'name' => 'Молдова'],
            ['flag' => '🇺🇦', 'name' => 'Украина'],
            ['flag' => '🌍', 'name' => 'Другое'],
        ];
    }
}
