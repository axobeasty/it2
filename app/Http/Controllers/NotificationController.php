<?php

namespace App\Http\Controllers;


use App\Models\Notifs;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {

    }
    public function create(string $title, string $message,int $id){
       $not = new Notifs();
        $not->title="$title";
        $not->message="$message";
        $not->id=$id;
        $not->save();

    }

    public function makeread(Request $request)
    {
        try{
            $user = $request->session()->get('user');
            $notifs = Notifs::where('employee_id',$user->id)->get();
            foreach ($notifs as $notif) {
                $notif->is_read = 1;
                $notif->save();
            }
            Toastr::success('Все уведомления отмечены как прочитанные!', 'Успешно', ["progressBar"=> true]);
        }catch (\Exception $exception){
            dd($exception);
        }
        finally{
            return redirect()->back();
        }
    }
}
