<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Notifs;
use App\Models\O_Categories;
use App\Models\Orders;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function my(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            $O_categories = O_Categories::all();
            $orders = Orders::where('employee_id',$user->id)->get();
            return view('orders.my',compact('user','settings','orders','O_categories'));
        }else{
            return redirect('/');
        }
    }

    public function create(Request $request)
    {
        $user = $request->session()->get('user');
       if($request->session()->has('user')){
try{
    $data2[] = $request->input();
    $ord = new Orders();
    $request->validate([
        'description' => 'required|string|max:1000',
        'cabinetik' => 'nullable|string|max:20',
        'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,png,txt', // до 10 МБ
    ]);
    $ord->employee_id = $user->id;
    $ord->description = $data2[0]["description"];
    $ord->category_id =$data2[0]["category"];
    $ord->room=$data2[0]["cabinetik"];
    $ord->status = 0;
    if ($request->hasFile('file')) {
        $path = $request->file('file')->store('uploads/orders', 'public');
        $ord->file_path = $path;
    }
    $ord -> save();
    Notifs::create([
        'title'=>'Заявка зарегистрирована',
        'message'=>'Ваша заявка успешно зарегистрирована в системе под идентификатором '.$ord->id.'. Ожидайте решения.',
        'employee_id' => $user->id,
    ]);
    Toastr::success('Успешно', 'Заявка успешно создана', ["progressBar"=> true]);
}catch (\Exception $e){
    Toastr::error($e->getMessage(), 'Произошла ошибка', ["progressBar"=> true]);
}
               return redirect()->back();
       }else{
           return redirect('/');
       }
    }

    public function categories(Request $request)
    {
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('orders_admin')){
                $categories = O_Categories::all();
                $settings = Settings::where('id',1)->first();
                return view('orders.categories',compact('user','settings','categories'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function notallowed()
    {
        Toastr::warning('Неверный запрос', 'Проверьте Ваш запрос', ["progressBar"=> true]);
        return redirect('/');
    }

    public function c_category(Request $request)
    {
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('orders_admin')){
                $data[] = $request->input();
                $new = new O_Categories();
                $new->name = $data[0]['name'];
                $new->cat_color = $data[0]['cat_color'];
                $new->save();
                Toastr::success('Успешно', 'Категория упешно создана!', ["progressBar"=> true]);
                return redirect()->back();
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function d_category(Request $request,string $id)
    {
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('orders_admin')){
                $delete = O_Categories::where('id',$id)->delete();
                Toastr::success('Успешно', 'Категория упешно удалена!', ["progressBar"=> true]);
                return redirect()->back();
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function administration(Request $request)
    {
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('orders_admin')){
                $settings = Settings::where('id',1)->first();
                $employees = Employee::all();
                $O_categories = O_Categories::all();
                $orders = Orders::with('category', 'employee:id,fio')->get();
                return view('orders.administration',compact('user','settings','orders','employees','O_categories'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function UpdateStatus(string $id, int $code, Request $request)
    {
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('orders_admin')){
                $order = Orders::where('id',$id)->first();
                $order->status = $code;
                $order->save();
                switch ($code){
                    case "0":{
                        Notifs::create([
                            'title'=>'Обновление статуса заявки',
                            'message'=>'Статус Вашей заявки с номером '.$order->id.' изменен на НОВАЯ.',
                            'employee_id' => $order->employee_id,
                        ]);
                        break;
                    }
                    case "1":{
                        Notifs::create([
                            'title'=>'Обновление статуса заявки',
                            'message'=>'Ваша заявка с номером '.$order->id.' сейчас в работе. Скоро ваш вопрос будет решен.',
                            'employee_id' => $order->employee_id,
                        ]);
                        break;
                    }
                    case "2":{
                        Notifs::create([
                            'title'=>'Обновление статуса заявки',
                            'message'=>'Ваша заявка с номером '.$order->id.' обработана.',
                            'employee_id' => $order->employee_id,
                        ]);
                        break;
                    }
                    case "3":{
                        Notifs::create([
                            'title'=>'Обновление статуса заявки',
                            'message'=>'Ваша заявка с номером '.$order->id.' закрыта.',
                            'employee_id' => $order->employee_id,
                        ]);
                        break;
                    }
                }

                Toastr::success('Статус заявки успешно изменен', 'Заявка '.$id.' обновлена', ["progressBar"=> true]);
                return redirect()->back();
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
}
