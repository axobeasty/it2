<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Portfolio;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class PortfolioConfirmationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id', 1)->first();

        if (! $user || ! $user->canAccessPage('portfolio_confirm')) {
            Toastr::error('Нет доступа к подтверждению портфолио.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $employees = Employee::query()->orderBy('fio')->get();

        $employeeId = $request->query('employee_id');
        $selected = null;
        $items = collect();

        if ($employeeId) {
            $selected = Employee::query()->find((int) $employeeId);
            if ($selected) {
                $items = Portfolio::query()
                    ->where('employee_id', $selected->id)
                    ->with(['portfolioType', 'employee'])
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        return view('dashboard.portfolio_confirm', [
            'user' => $user,
            'settings' => $settings,
            'employees' => $employees,
            'selected' => $selected,
            'items' => $items,
        ]);
    }

    public function approve(Request $request, int $portfolio)
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('portfolio_confirm')) {
            Toastr::error('Нет доступа.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $entry = Portfolio::query()->findOrFail($portfolio);
        if ((int) $entry->status !== 0) {
            Toastr::warning('Запись уже обработана.', 'Внимание', ['progressBar' => true]);

            return redirect()->route('portfolio.confirm', ['employee_id' => $entry->employee_id]);
        }

        $entry->update(['status' => 1]);
        Toastr::success('Позиция портфолио утверждена.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('portfolio.confirm', ['employee_id' => $entry->employee_id]);
    }

    public function reject(Request $request, int $portfolio)
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('portfolio_confirm')) {
            Toastr::error('Нет доступа.', 'Ошибка доступа', ['progressBar' => true]);

            return redirect('/dashboard');
        }

        $entry = Portfolio::query()->findOrFail($portfolio);
        if ((int) $entry->status !== 0) {
            Toastr::warning('Запись уже обработана.', 'Внимание', ['progressBar' => true]);

            return redirect()->route('portfolio.confirm', ['employee_id' => $entry->employee_id]);
        }

        $entry->update(['status' => 2]);
        Toastr::success('Позиция портфолио отклонена.', 'Успешно', ['progressBar' => true]);

        return redirect()->route('portfolio.confirm', ['employee_id' => $entry->employee_id]);
    }
}
