<?php

namespace App\Http\Controllers;


use App\Models\Notifs;
use App\Support\RequestPerformanceCache;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            Notifs::query()
                ->where('employee_id', (int) $user->id)
                ->update(['is_read' => true]);
            RequestPerformanceCache::forgetNotifUnreadCount((int) $user->id);
            Toastr::success('Все уведомления отмечены как прочитанные!', 'Успешно', ["progressBar"=> true]);
        }catch (\Exception $exception){
            Log::error('notifications.mark_all_read_failed', [
                'message' => $exception->getMessage(),
            ]);
            Toastr::error('Не удалось обновить уведомления.', 'Ошибка', ['progressBar' => true]);
        }

        return redirect()->back();
    }
}
