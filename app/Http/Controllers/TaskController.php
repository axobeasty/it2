<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Models\Task;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class TaskController extends Controller
{
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
                return redirect('/');
            }else{
                return view('settings.disabled',compact('user','settings'));
            }

        }else{
            return view('index',compact('settings'));
        }
    }
    public function delete($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        Toastr::success('Успешно', 'Задача успешно удалена', ["progressBar"=> true]);
        return redirect()->back()->with('success', 'Задача удалена.');
    }

    public function notallowed(){
        Toastr::warning('Неверный запрос', 'Проверьте Ваш запрос', ["progressBar"=> true]);
        return redirect('/');
    }
}
