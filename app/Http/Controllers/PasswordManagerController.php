<?php

namespace App\Http\Controllers;

use App\Models\EmployeePassword;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PasswordManagerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::where('id', 1)->first();

        if (!$request->session()->has('user')) {
            return redirect('/');
        }

        $passwords = EmployeePassword::where('employee_id', $user->id)
            ->latest()
            ->get();

        return view('passwords.index', compact('user', 'settings', 'passwords'));
    }

    public function store(Request $request)
    {
        $user = $request->session()->get('user');
        if (!$request->session()->has('user')) {
            return redirect('/');
        }

        $rawUrl = trim((string) $request->input('url', ''));
        if ($rawUrl !== '' && !preg_match('/^https?:\/\//i', $rawUrl)) {
            $request->merge(['url' => 'https://'.$rawUrl]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'login' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'string'],
        ]);

        EmployeePassword::create([
            'employee_id' => $user->id,
            'title' => $validated['title'],
            'login' => $validated['login'] ?? null,
            'url' => $validated['url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'password_encrypted' => Crypt::encryptString($validated['password']),
        ]);

        Toastr::success('Успешно', 'Пароль сохранен в защищенном хранилище', ["progressBar" => true]);
        return redirect('/passwords');
    }

    public function reveal(Request $request, int $id)
    {
        if (!$request->session()->has('user')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = $request->session()->get('user');
        $entry = EmployeePassword::where('id', $id)
            ->where('employee_id', $user->id)
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $password = Crypt::decryptString($entry->password_encrypted);
        } catch (DecryptException $e) {
            return response()->json(['message' => 'Decrypt failed'], 422);
        }

        return response()->json(['password' => $password]);
    }

    public function destroy(Request $request, int $id)
    {
        if (!$request->session()->has('user')) {
            return redirect('/');
        }

        $user = $request->session()->get('user');
        $entry = EmployeePassword::where('id', $id)
            ->where('employee_id', $user->id)
            ->first();

        if (!$entry) {
            Toastr::error('Ошибка', 'Запись не найдена или нет доступа', ["progressBar" => true]);
            return redirect('/passwords');
        }

        $entry->delete();
        Toastr::success('Успешно', 'Запись удалена', ["progressBar" => true]);
        return redirect('/passwords');
    }
}
