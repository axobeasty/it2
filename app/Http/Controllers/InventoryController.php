<?php

namespace App\Http\Controllers;

use App\Http\Email\Email;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Inv_Type;
use App\Models\InvNumbers;
use App\Models\Notifs;
use App\Models\O_Categories;
use App\Models\Settings;
use App\Models\Store;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class InventoryController extends Controller
{
    public function index(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $user = $request->session()->get('user');

            if($settings->is_enabled == 1 || $user->canAccessPage('maintenance_bypass')){
                $numbers = InvNumbers::with(['store.type'])
                    ->where('employees_id', $user->id)
                    ->orderByDesc('date_in')
                    ->get();

                return view('inventory.my', compact('user','settings','numbers'));
            }else{
                return view('/',compact('user','settings'));
            }

        }else{
            return redirect('/');
        }

    }

    public function manage(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $user = $request->session()->get('user');

            if($user->canAccessPage('inventory_admin')){
                $employeePages = InvNumbers::query()
                    ->whereNull('date_out')
                    ->select('employees_id')
                    ->groupBy('employees_id')
                    ->orderBy('employees_id')
                    ->paginate(20)
                    ->withQueryString();
                $employeeIds = collect($employeePages->items())->pluck('employees_id')->all();
                $numbers = InvNumbers::with(['store.type', 'employee'])
                    ->whereNull('date_out')
                    ->whereIn('employees_id', $employeeIds)
                    ->orderBy('employees_id')
                    ->orderBy('id')
                    ->get();
                $employees = Employee::all();
                $groupedByEmployee = $numbers->groupBy('employees_id');

                return view('inventory.manage', compact('user','settings','employees','numbers','groupedByEmployee', 'employeePages'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }

        }else{
            return redirect('/');
        }

    }

    public function assign(Request $request)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user') || ! $user->canAccessPage('inventory_admin')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $validated = $request->validate([
            'employees_id' => ['required', 'exists:employees,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.room' => ['required', 'string', 'max:255'],
            'items.*.number' => ['required', 'string', 'max:255'],
        ]);

        $type = Inv_Type::orderBy('id')->first();
        if (!$type) {
            $type = new Inv_Type();
            $type->name = 'Прочее';
            $type->save();
        }

        $assignedItems = [];
        foreach ($validated['items'] as $rawItem) {
            $number = trim((string) $rawItem['number']);
            $itemName = trim((string) $rawItem['item_name']);
            $room = trim((string) $rawItem['room']);

            $store = Store::where('inv_number', $number)->first();
            if (!$store) {
                $store = new Store();
                $store->name = $itemName;
                $store->inv_number = $number;
                $store->count = 1;
                $store->inv_type_id = $type->id;
                $store->is_enabled = 1;
                $store->save();
            } elseif (trim((string) $store->name) === '') {
                $store->name = $itemName;
                $store->save();
            }

            InvNumbers::create([
                'number' => $number,
                'date_in' => Carbon::now()->toDateString(),
                'date_out' => null,
                'room' => $room,
                'employees_id' => (int) $validated['employees_id'],
                'store_id' => (int) $store->id,
            ]);

            $assignedItems[] = [
                'name' => $store->name ?? $itemName,
                'number' => $number,
                'room' => $room,
            ];
        }

        $employee = Employee::find((int) $validated['employees_id']);
        if ($employee) {
            $this->sendInventoryEmail(
                $employee,
                'За вами закрепили инвентарь',
                'За вами закрепили следующий инвентарь:',
                $assignedItems ?? []
            );
            $this->createInventorySiteNotification(
                $employee->id,
                'Закрепление инвентаря',
                $this->buildItemsNotificationMessage('За вами закрепили инвентарь:', $assignedItems ?? [])
            );
        }

        Toastr::success('Успешно', 'Инвентарь закреплен за сотрудником', ["progressBar"=> true]);
        return redirect('/inv/manage');
    }

    public function unassign(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user') || ! $user->canAccessPage('inventory_admin')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $invNumber = InvNumbers::findOrFail($id);
        $employee = $invNumber->employee;
        $invNumber->date_out = Carbon::now()->toDateString();
        $invNumber->save();

        if ($employee) {
            $this->sendInventoryEmail(
                $employee,
                'От вас открепили инвентарь',
                'От вас открепили следующий предмет:',
                [[
                    'name' => $invNumber->store->name ?? 'Без названия',
                    'number' => $invNumber->number ?? '',
                    'room' => $invNumber->room ?? '',
                ]]
            );
            $this->createInventorySiteNotification(
                $employee->id,
                'Открепление инвентаря',
                $this->buildItemsNotificationMessage('От вас открепили:', [[
                    'name' => $invNumber->store->name ?? 'Без названия',
                    'number' => $invNumber->number ?? '',
                    'room' => $invNumber->room ?? '',
                ]])
            );
        }

        Toastr::success('Успешно', 'Инвентарь откреплен от сотрудника', ["progressBar"=> true]);
        return redirect('/inv/manage');
    }

    public function unassignAll(Request $request, int $employeeId)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user') || ! $user->canAccessPage('inventory_admin')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            Toastr::error('Ошибка', 'Сотрудник не найден', ["progressBar"=> true]);
            return redirect('/inv/manage');
        }

        $activeItems = InvNumbers::with('store')
            ->where('employees_id', $employeeId)
            ->whereNull('date_out')
            ->get();

        if ($activeItems->isEmpty()) {
            Toastr::info('Информация', 'У сотрудника нет активных закреплений', ["progressBar"=> true]);
            return redirect('/inv/manage');
        }

        $unassignedItems = [];
        foreach ($activeItems as $item) {
            $item->date_out = Carbon::now()->toDateString();
            $item->save();

            $unassignedItems[] = [
                'name' => $item->store->name ?? 'Без названия',
                'number' => $item->number ?? '',
                'room' => $item->room ?? '',
            ];
        }

        $this->sendInventoryEmail(
            $employee,
            'От вас открепили инвентарь',
            'От вас открепили следующий инвентарь:',
            $unassignedItems
        );

        $this->createInventorySiteNotification(
            $employee->id,
            'Открепление инвентаря',
            $this->buildItemsNotificationMessage('От вас открепили инвентарь:', $unassignedItems)
        );

        Toastr::success('Успешно', 'Все активные предметы откреплены от сотрудника', ["progressBar"=> true]);
        return redirect('/inv/manage');
    }

    public function reassign(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user') || ! $user->canAccessPage('inventory_admin')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $invNumber = InvNumbers::findOrFail($id);
        if (!$invNumber->date_out) {
            Toastr::info('Инвентарь уже закреплен', 'Информация', ["progressBar"=> true]);
            return redirect('/inv/manage');
        }

        $invNumber->date_in = Carbon::now()->toDateString();
        $invNumber->date_out = null;
        $invNumber->save();

        if ($invNumber->employee) {
            $this->sendInventoryEmail(
                $invNumber->employee,
                'Инвентарь снова закреплен за вами',
                'За вами снова закрепили следующий предмет:',
                [[
                    'name' => $invNumber->store->name ?? 'Без названия',
                    'number' => $invNumber->number ?? '',
                    'room' => $invNumber->room ?? '',
                ]]
            );
            $this->createInventorySiteNotification(
                $invNumber->employee->id,
                'Повторное закрепление инвентаря',
                $this->buildItemsNotificationMessage('За вами снова закрепили:', [[
                    'name' => $invNumber->store->name ?? 'Без названия',
                    'number' => $invNumber->number ?? '',
                    'room' => $invNumber->room ?? '',
                ]])
            );
        }

        Toastr::success('Успешно', 'Инвентарь снова закреплен', ["progressBar"=> true]);
        return redirect('/inv/manage');
    }

    private function sendInventoryEmail(Employee $employee, string $title, string $intro, array $items): void
    {
        $address = trim((string) ($employee->email ?? ''));
        if ($address === '') {
            return;
        }

        $rows = '';
        foreach ($items as $index => $item) {
            $name = e((string) ($item['name'] ?? 'Без названия'));
            $number = e((string) ($item['number'] ?? '—'));
            $room = e((string) ($item['room'] ?? '—'));
            $rows .= '<li><b>'.($index + 1).'. '.$name.'</b> (Инв. номер: '.$number.', Кабинет: '.$room.')</li>';
        }

        $body = 'Здравствуйте, '.$employee->fio.'!<br><br>'.$intro.'<br><ul>'.$rows.'</ul>';

        try {
            $email = new Email();
            $email->send($title, $body, $address, 'IT-Master');
        } catch (Throwable $e) {
            // Не прерываем бизнес-операцию из-за проблем с почтой.
        }
    }

    private function createInventorySiteNotification(int $employeeId, string $title, string $message): void
    {
        Notifs::create([
            'title' => $title,
            'message' => mb_substr($message, 0, 250, 'UTF-8'),
            'employee_id' => $employeeId,
        ]);
    }

    private function buildItemsNotificationMessage(string $prefix, array $items): string
    {
        $chunks = [];
        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? 'Без названия'));
            $number = trim((string) ($item['number'] ?? '—'));
            $chunks[] = $name.' (№ '.$number.')';
        }

        if (empty($chunks)) {
            return $prefix;
        }

        return $prefix.' '.implode('; ', $chunks);
    }

    public function export(Request $request)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user') || ! $user->canAccessPage('inventory_admin')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $fileName = 'inventory_print_'.now()->format('Y-m-d_H-i').'.csv';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Сотрудник',
                'ID сотрудника',
                '№',
                'Предмет',
                'Инвентарный номер',
                'Кабинет',
                'Дата закрепления',
                'Пометки (ручкой/карандашом)',
                'Подпись',
            ], ';');

            $counters = [];
            InvNumbers::query()
                ->with(['store:id,name', 'employee:id,fio'])
                ->whereNull('date_out')
                ->orderBy('employees_id')
                ->orderBy('id')
                ->chunkById(500, function ($chunk) use (&$counters, $output) {
                    foreach ($chunk as $item) {
                        $employeeId = (int) $item->employees_id;
                        $counters[$employeeId] = ($counters[$employeeId] ?? 0) + 1;
                        fputcsv($output, [
                            optional($item->employee)->fio ?? '—',
                            $employeeId ?: '—',
                            $counters[$employeeId],
                            optional($item->store)->name ?? 'Без названия',
                            $item->number ?? '',
                            $item->room ?? '',
                            $item->date_in ?? '',
                            '',
                            '',
                        ], ';');
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user') || ! $user->canAccessPage('inventory_admin')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $numbers = InvNumbers::with(['store.type', 'employee'])
            ->whereNull('date_out')
            ->orderBy('employees_id')
            ->orderBy('id')
            ->get();

        $groupedByEmployee = $numbers->groupBy('employees_id');
        $printedAt = now()->format('d.m.Y H:i');

        return view('inventory.print', compact('groupedByEmployee', 'printedAt'));
    }

    public function types(Request $request){
        $settings = Settings::where('id',1)->first();
        if($request->session()->has('user')){
            $user = $request->session()->get('user');

            if($settings->is_enabled == 1 || $user->canAccessPage('maintenance_bypass')){
                $inv_type= Inv_Type::all();
                return view('inventory.types', compact('user','settings','inv_type'));
            }else{
                return view('/',compact('user','settings'));
            }

        }else{
            return redirect('/');
        }

    }

    public function departments(Request $request)
    {
        $settings = Settings::where('id',1)->first();
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('inventory_admin') ){
                $departs = Department::all();
                $O_categories = O_Categories::all();
                $contingent = Employee::all();

                return view('inventory.departments', compact('user','departs','settings','O_categories','contingent'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }

    }

    public function dep_create(request $request)
    {
        $settings = Settings::where('id',1)->first();
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('inventory_admin') ){
                $contingent = Employee::all();
                $new = new Department();
                $new->title = $request->title;
                $new ->save();
                if($request->employee_ids != null){
                    try{
                        Employee::whereIn('id', array_map('intval', (array) $request->employee_ids))
                            ->update(['department_id' => $new->id]);
                    }catch (\Exception $exception){
                        Toastr::error('Не удалось привязать сотрудников к созданному подразделению '.$exception->getMessage(), 'Ошибка!', ["progressBar"=> true]);
                    }
                }

                Toastr::success('Подразделение успешно создано', 'Готово', ["progressBar"=> true]);
                return redirect()->back();
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function dep_delete(Request $request,int $id)
    {
        $settings = Settings::where('id',1)->first();
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('inventory_admin') ){
                Employee::where('department_id', $id)->update(['department_id' => 1]);
                $dep = Department::where('id',$id)->first();
                $dep->delete();
                Toastr::success('Подразделение успешно удалено', 'Готово', ["progressBar" => true]);
                return redirect()->back();
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function dep_edit(Request $request,int $id)
    {
        $settings = Settings::where('id',1)->first();
        $user = $request->session()->get('user');
        if($request->session()->has('user')){
            if($user->canAccessPage('inventory_admin') ){
                $editer = Department::where('id',$id)->first();
                $editer->title = $request->title;
                if($request->employee_ids != null){
                    try{
                        Employee::whereIn('id', array_map('intval', (array) $request->employee_ids))
                            ->update(['department_id' => $id]);
                    }catch (\Exception $exception){
                        Toastr::error('Не удалось привязать сотрудников к подразделению '.$exception->getMessage(), 'Ошибка!', ["progressBar"=> true]);
                    }
                }
                $editer->save();
                Toastr::success('Подразделение обновлено', 'Готово', ["progressBar"=> true]);
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
